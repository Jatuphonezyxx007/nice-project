<?php
require_once '../config.php';
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';
$message_type = 'info';

// (โค้ด PHP ด้านบน... เหมือนเดิม) ...
$stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$attendance_today = $result->fetch_assoc();
$stmt->close();

$has_checked_in = false;
$has_checked_out = false;
if ($attendance_today) {
    $has_checked_in = true;
    if ($attendance_today['check_out_time'] !== null) {
        $has_checked_out = true;
    }
}
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<title>หน้าหลักพนักงาน - ลงเวลา</title>

<style>
    #webcam-container video {
        width: 100%;
        max-width: 500px;
        height: auto;
        border-radius: 8px;
        border: 2px solid #ddd;
    }

    /* ซ่อน canvas ที่ใช้ดึงรูป */
    .snapshot-canvas {
        display: none;
    }

    width: 100%;
        height: auto;
        border-radius: 8px;
        border: 2px solid #ddd;
    }

    /* ซ่อน canvas ที่ใช้ดึงรูป */
    .snapshot-canvas {
        display: none;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">ลงเวลาปฏิบัติงาน</h4>
                    <p class="mb-0">วันที่: <?php echo date('d F Y'); ?></p>
                </div>
                <div class="card-body text-center">

                    <h2 id="realtime-clock" class="display-5 fw-bold text-secondary"></h2>
                    <hr>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <?php if ($has_checked_out): ?>
                        <h5 class="text-success">คุณได้ลงเวลาเข้า-ออก ของวันนี้เรียบร้อยแล้ว</h5>
                        <p>เวลาเข้า: <?php echo date('H:i:s', strtotime($attendance_today['check_in_time'])); ?></p>
                        <p>เวลาออก: <?php echo date('H:i:s', strtotime($attendance_today['check_out_time'])); ?></p>

                    <?php elseif ($has_checked_in): ?>
                        <h5 class="text-info">ลงเวลาเข้าเรียบร้อยแล้ว
                            (<?php echo date('H:i:s', strtotime($attendance_today['check_in_time'])); ?>)</h5>

                        <div id="webcam-container-checkout" class="mb-3">
                            <video id="video-checkout" autoplay playsinline></video>
                            <canvas id="canvas-checkout" class="snapshot-canvas"></canvas>
                        </div>

                        <form id="form-checkout" action="checkout.php" method="POST" class="mt-3">
                            <input type="hidden" name="checkout_image_data" id="image-checkout-data">

                            <button type="button" id="snap-checkout" class="btn btn-warning w-100 mb-2">
                                <i class="bi bi-camera-fill me-2"></i> 1. ถ่ายภาพ
                            </button>

                            <button type="submit" id="submit-checkout" class="btn btn-danger w-100" disabled>
                                2. ยืนยันลงเวลาออก (Check-out)
                            </button>
                        </form>

                    <?php else: ?>
                        <h5 class="text-muted">กรุณาลงเวลาเข้าปฏิบัติงาน</h5>

                        <div id="webcam-container-checkin" class="mb-3">
                            <video id="video-checkin" autoplay playsinline></video>
                            <canvas id="canvas-checkin" class="snapshot-canvas"></canvas>
                        </div>

                        <form id="form-checkin" action="checkin.php" method="POST" class="mt-3">
                            <input type="hidden" name="checkin_image_data" id="image-checkin-data">

                            <button type="button" id="snap-checkin" class="btn btn-warning w-100 mb-2">
                                <i class="bi bi-camera-fill me-2"></i> 1. ถ่ายภาพ
                            </button>

                            <button type="submit" id="submit-checkin" class="btn btn-success w-100" disabled>
                                2. ยืนยันลงเวลาเข้า (Check-in)
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- 1. ฟังก์ชันนาฬิกา Real-time ---
        const clockElement = document.getElementById('realtime-clock');

        function startClock() {
            if (!clockElement) return; // ถ้าไม่มีนาฬิกาก็ไม่ต้องทำ

            function updateTime() {
                const now = new Date();
                // H:i:s (เช่น 14:05:02)
                const timeString = now.toTimeString().split(' ')[0];
                clockElement.textContent = timeString;
            }
            updateTime(); // รันครั้งแรกเลย
            setInterval(updateTime, 1000); // อัปเดตทุก 1 วินาที
        }

        startClock(); // สั่งให้นาฬิกาทำงาน

        // --- 2. ฟังก์ชันกล้อง ---

        // ตั้งค่ากล้อง (เราต้องการกล้องหน้ามือถือ)
        const constraints = {
            video: {
                facingMode: 'user' // ใช้กล้องหน้า
            }
        };

        // ฟังก์ชันเริ่มกล้อง
        async function startCamera(videoElement) {
            if (!videoElement) return;
            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                videoElement.srcObject = stream;
            } catch (err) {
                console.error("Error accessing webcam: ", err);
                alert("ไม่สามารถเปิดกล้องได้ อาจจะยังไม่ได้อนุญาต (Allow) หรือบราวเซอร์ไม่รองรับ (ต้องใช้ HTTPS)");
            }
        }

        // ฟังก์ชันถ่ายภาพ
        function takeSnapshot(video, canvas, hiddenInput, submitButton) {
            const context = canvas.getContext('2d');

            // ตั้งค่าขนาด canvas ให้เท่ากับ video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // วาดภาพจาก video ลง canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // แปลงภาพใน canvas เป็น Base64 (แบบ JPEG)
            const imageData = canvas.toDataURL('image/jpeg', 0.9); // คุณภาพ 90%

            // ใส่ข้อมูล Base64 ลงใน input ที่ซ่อนไว้
            hiddenInput.value = imageData;

            // (สำคัญ) เปิดปุ่ม "ยืนยัน"
            submitButton.disabled = false;

            alert("ถ่ายภาพเรียบร้อย! กรุณากดปุ่ม 'ยืนยัน'");
        }

        // --- 3. เชื่อมต่อฟังก์ชันเข้ากับปุ่ม ---

        // (สำหรับ Check-in)
        const videoCheckin = document.getElementById('video-checkin');
        const canvasCheckin = document.getElementById('canvas-checkin');
        const snapCheckin = document.getElementById('snap-checkin');
        const imageCheckinData = document.getElementById('image-checkin-data');
        const submitCheckin = document.getElementById('submit-checkin');

        if (videoCheckin) {
            startCamera(videoCheckin); // เปิดกล้อง Check-in
            snapCheckin.addEventListener('click', function () {
                takeSnapshot(videoCheckin, canvasCheckin, imageCheckinData, submitCheckin);
            });
        }

        // (สำหรับ Check-out)
        const videoCheckout = document.getElementById('video-checkout');
        const canvasCheckout = document.getElementById('canvas-checkout');
        const snapCheckout = document.getElementById('snap-checkout');
        const imageCheckoutData = document.getElementById('image-checkout-data');
        const submitCheckout = document.getElementById('submit-checkout');

        if (videoCheckout) {
            startCamera(videoCheckout); // เปิดกล้อง Check-out
            snapCheckout.addEventListener('click', function () {
                takeSnapshot(videoCheckout, canvasCheckout, imageCheckoutData, submitCheckout);
            });
        }

    });
</script>

<?php
$conn->close();
require_once '../includes/footer.php';
?>