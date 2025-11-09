<?php
require_once '../config.php';
// เรียก header.php (ซึ่งจะตรวจสอบการ login ให้อัตโนมัติ)
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$message = '';
$message_type = 'info';

// ค้นหาข้อมูลการลงเวลาของวันนี้
$stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$attendance_today = $result->fetch_assoc();
$stmt->close();

// ตรวจสอบสถานะ
$has_checked_in = false;
$has_checked_out = false;

if ($attendance_today) {
    $has_checked_in = true;
    if ($attendance_today['check_out_time'] !== null) {
        $has_checked_out = true;
    }
}

// ตรวจสอบถ้ามีการส่งข้อความแจ้งเตือนมา (จาก checkin.php/checkout.php)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>

<title>หน้าหลักพนักงาน - ลงเวลา</title>

<div class="container"
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">ลงเวลาปฏิบัติงาน</h4>
                <p class="mb-0">วันที่: <?php echo date('d F Y'); ?></p>
            </div>
            <div class="card-body text-center">
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if ($has_checked_out): ?>
                    <h5 class="text-success">คุณได้ลงเวลาเข้า-ออก ของวันนี้เรียบร้อยแล้ว</h5>
                    <p>เวลาเข้า: <?php echo date('H:i:s', strtotime($attendance_today['check_in_time'])); ?></p>
                    <p>เวลาออก: <?php echo date('H:i:s', strtotime($attendance_today['check_out_time'])); ?></p>

                <?php elseif ($has_checked_in): ?>
                    <h5 class="text-info">ลงเวลาเข้าเรียบร้อยแล้ว</h5>
                    <p>เวลาเข้า: <?php echo date('H:i:s', strtotime($attendance_today['check_in_time'])); ?></p>
                    <form action="checkout.php" method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <label for="checkout_image" class="form-label">แนบรูปภาพสำหรับลงเวลาออก <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="checkout_image" name="checkout_image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">ลงเวลาออก (Check-out)</button>
                    </form>

                <?php else: ?>
                    <h5 class="text-muted">กรุณาลงเวลาเข้าปฏิบัติงาน</h5>
                    <form action="checkin.php" method="POST" enctype="multipart/form-data" class="mt-3">
                        <div class="mb-3">
                            <label for="checkin_image" class="form-label">แนบรูปภาพสำหรับลงเวลาเข้า <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="checkin_image" name="checkin_image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">ลงเวลาเข้า (Check-in)</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>