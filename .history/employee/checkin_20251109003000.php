<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// (อัปเดต) เปลี่ยนจากการเช็ค $_FILES เป็น $_POST['...image_data']
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin_image_data'])) {

    $user_id = $_SESSION['user_id'];
    $filename = '';
    $image_data = $_POST['checkin_image_data'];

    // --- 1. (อัปเดต) จัดการรูปภาพจาก Base64 ---
    if (empty($image_data)) {
        $_SESSION['message'] = "ข้อมูลรูปภาพว่างเปล่า กรุณาถ่ายภาพใหม่";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }

    // $image_data จะมีหน้าตาประมาณ "data:image/jpeg;base64,iVBORw0KGgo..."
    // เราต้องตัดส่วนหัว "data:image/jpeg;base64," ออก
    list($type, $data) = explode(';', $image_data);
    list(, $data) = explode(',', $data);

    // ถอดรหัส Base64
    $decoded_data = base64_decode($data);

    if ($decoded_data === false) {
        $_SESSION['message'] = "รูปแบบข้อมูลรูปภาพไม่ถูกต้อง";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }

    $target_dir = "../assets/uploads/attendance/";
    // สร้างชื่อไฟล์ใหม่ (เรากำหนดเป็น .jpg เพราะเราสั่งให้ JS บีบเป็น JPEG)
    $filename = $user_id . '_' . time() . '.jpg';
    $target_file = $target_dir . $filename;

    // (อัปเดต) เปลี่ยนจาก move_uploaded_file เป็น file_put_contents
    if (file_put_contents($target_file, $decoded_data)) {
        // บันทึกไฟล์สำเร็จ
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการบันทึกรูปภาพ";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }
    // --- จบส่วนที่ 1 ---


    // --- 2. คำนวณสถานะ (สาย/ตรงเวลา) ---
    // (โค้ดส่วนนี้เหมือนเดิม 100%)
    $status = 'on_time';
    $stmt_schedule = $conn->prepare("SELECT s.time_in FROM users u JOIN schedule_types s ON u.schedule_id = s.id WHERE u.id = ?");
    $stmt_schedule->bind_param("i", $user_id);
    $stmt_schedule->execute();
    $result_schedule = $stmt_schedule->get_result();

    if ($result_schedule->num_rows > 0) {
        $schedule = $result_schedule->fetch_assoc();
        $time_in_schedule = strtotime($schedule['time_in']);
        $current_time = strtotime(date('H:i:s'));

        if ($current_time > $time_in_schedule) {
            $status = 'late';
        }
    }
    $stmt_schedule->close();

    // --- 3. บันทึกลงฐานข้อมูล ---
    // (โค้ดส่วนนี้เหมือนเดิม 100%)
    $stmt_insert = $conn->prepare("INSERT INTO attendance (user_id, attendance_date, check_in_time, check_in_image, check_in_status) VALUES (?, CURDATE(), NOW(), ?, ?)");
    $stmt_insert->bind_param("iss", $user_id, $filename, $status);

    if ($stmt_insert->execute()) {
        $_SESSION['message'] = "ลงเวลาเข้าสำเร็จ!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาด: " . $stmt_insert->error;
        $_SESSION['message_type'] = "danger";
    }
    $stmt_insert->close();

} else {
    $_SESSION['message'] = "การร้องขอไม่ถูกต้อง (ไม่พบรูปภาพ)";
    $_SESSION['message_type'] = "danger";
}

$conn->close();
header('Location: dashboard.php');
exit;
?>