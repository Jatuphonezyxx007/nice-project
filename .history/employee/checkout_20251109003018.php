<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// (อัปเดต) เปลี่ยนการตรวจสอบ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout_image_data'])) {

    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $filename = '';
    $image_data = $_POST['checkout_image_data'];

    // --- 1. (อัปเดต) จัดการรูปภาพจาก Base64 ---
    if (empty($image_data)) {
        $_SESSION['message'] = "ข้อมูลรูปภาพว่างเปล่า กรุณาถ่ายภาพใหม่";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }

    list($type, $data) = explode(';', $image_data);
    list(, $data) = explode(',', $data);
    $decoded_data = base64_decode($data);

    if ($decoded_data === false) {
        $_SESSION['message'] = "รูปแบบข้อมูลรูปภาพไม่ถูกต้อง";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }

    $target_dir = "../assets/uploads/attendance/";
    $filename = $user_id . '_' . time() . '.jpg';
    $target_file = $target_dir . $filename;

    if (!file_put_contents($target_file, $decoded_data)) {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการบันทึกรูปภาพ";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }
    // --- จบส่วนที่ 1 ---


    // --- 2. อัปเดตฐานข้อมูล ---
    // (โค้ดส่วนนี้เหมือนเดิม 100%)
    $stmt_update = $conn->prepare("UPDATE attendance SET check_out_time = NOW(), check_out_image = ? WHERE user_id = ? AND attendance_date = ? AND check_out_time IS NULL");
    $stmt_update->bind_param("sis", $filename, $user_id, $today);

    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            $_SESSION['message'] = "ลงเวลาออกสำเร็จ!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "ไม่พบข้อมูลการลงเวลาเข้า หรือคุณลงเวลาออกไปแล้ว";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาด: " . $stmt_update->error;
        $_SESSION['message_type'] = "danger";
    }
    $stmt_update->close();

} else {
    $_SESSION['message'] = "การร้องขอไม่ถูกต้อง (ไม่พบรูปภาพ)";
    $_SESSION['message_type'] = "danger";
}

$conn->close();
header('Location: dashboard.php');
exit;
?>