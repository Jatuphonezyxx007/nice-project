<?php
require_once '../config.php';
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';
$message_type = 'info';

// (*** แก้ไข: 1. ดึงข้อมูล 2 ส่วนพร้อมกัน ***)

// 1.1 ดึงข้อมูลการลงเวลาของวันนี้ (ถ้ามี)
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

// 1.2 (*** โค้ดใหม่: ดึงกะของพนักงานในวันนี้ ***)
// นี่คือ Logic เดียวกับที่ใช้ใน admin/dashboard.php และ attendance.php
$stmt_schedule = $conn->prepare(
    "SELECT s.name as schedule_name, s.time_in, s.time_out
     FROM users u
     LEFT JOIN employee_schedules es ON u.id = es.user_id 
         AND CURDATE() BETWEEN es.start_date AND es.end_date
     LEFT JOIN schedule_types s ON es.schedule_id = s.id
     WHERE u.id = ? AND es.schedule_id IS NOT NULL AND es.schedule_id > 0" // (เพิ่ม > 0 กันกะ "ไม่กำหนด")
);
$stmt_schedule->bind_param("i", $user_id);
$stmt_schedule->execute();
$schedule_result = $stmt_schedule->get_result();
$user_schedule_today = $schedule_result->fetch_assoc(); // จะเป็น null ถ้าไม่มีกะ
$stmt_schedule->close();
// (*** จบโค้ดใหม่ ***)


if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<title>หน้าหลักพนักงาน - ลงเวลา</title>

<style>
    /* ... (Style - เหมือนเดิม) ... */
    #webcam-container-checkin video,
    #webcam-container-checkout video {
        width: 100%;
        height: auto;
        border-radius: 8px;
        border: 2px solid #ddd;
    }

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

                    <div class="mb-3">
                        <?php if ($user_schedule_today): ?>
                            <h5 class="text-success">
                                <i class="bi bi-clock-fill me-2"></i>
                                กะของคุณวันนี้: <?php echo htmlspecialchars($user_schedule_today['schedule_name']); ?>
                            </h5>
                            <p class="h6 fw-normal text-muted">
                                (<?php echo date('H:i', strtotime($user_schedule_today['time_in'])); ?> -
                                <?php echo date('H:i', strtotime($user_schedule_today['time_out'])); ?>)
                            </p>
                        <?php else: ?>
                            <h5 class="text-danger">
                                <i class="bi bi-calendar-x-fill me-2"></i>
                                วันนี้คุณไม่มีกะที่ได้รับมอบหมาย
                            </h5>
                        <?php endif; ?>
                    </div>
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

                    <?php elseif ($user_schedule_today): // (*** แก้ไข ***) ต้องมีกะ ถึงจะ Check-in ได้ ?>
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

                    <?php else: // (*** โค้ดใหม่ ***) กรณีไม่มีกะ และยังไม่เคยลงเวลา ?>
                        <h5 class="text-muted">ไม่สามารถลงเวลาได้</h5>
                        <p>เนื่องจากคุณไม่มีกะที่ได้รับมอบหมายในวันนี้</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ... (JavaScript ทั้งหมดเหมือนเดิม ไม่ต้องแก้ไข) ...
    document.addEventListener('DOMContentLoaded', function () {
        const clockElement = document.getElementById('realtime-clock');
        function startClock() {
            if (!clockElement) return;
            function updateTime() {
                const now = new Date();
                const timeString = now.toTimeString().split(' ')[0];
                clockElement.textContent = timeString;
            }
            updateTime();
            setInterval(updateTime, 1000);
        }
        startClock();
        const constraints = {
            video: {
                facingMode: 'user'
            }
        };
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
        function takeSnapshot(video, canvas, hiddenInput, submitButton) {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/jpeg', 0.9);
            hiddenInput.value = imageData;
            submitButton.disabled = false;
            alert("ถ่ายภาพเรียบร้อย! กรุณากดปุ่ม 'ยืนยัน'");
        }
        const videoCheckin = document.getElementById('video-checkin');
        const canvasCheckin = document.getElementById('canvas-checkin');
        const snapCheckin = document.getElementById('snap-checkin');
        const imageCheckinData = document.getElementById('image-checkin-data');
        const submitCheckin = document.getElementById('submit-checkin');
        if (videoCheckin) {
            startCamera(videoCheckin);
            snapCheckin.addEventListener('click', function () {
                takeSnapshot(videoCheckin, canvasCheckin, imageCheckinData, submitCheckin);
            });
        }
        const videoCheckout = document.getElementById('video-checkout');
        const canvasCheckout = document.getElementById('canvas-checkout');
        const snapCheckout = document.getElementById('snap-checkout');
        const imageCheckoutData = document.getElementById('image-checkout-data');
        const submitCheckout = document.getElementById('submit-checkout');
        if (videoCheckout) {
            startCamera(videoCheckout);
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