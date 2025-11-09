<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['checkout_image'])) {
    
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');
    $filename = '';

    // --- 1. จัดการการอัปโหลดไฟล์ ---
    if ($_FILES['checkout_image']['error'] == 0) {
        $target_dir = "../assets/uploads/attendance/";
        $filename = $user_id . '_' . time() . '_' . basename($_FILES["checkout_image"]["name"]);
        $target_file = $target_dir . $filename;

        if (!move_uploaded_file($_FILES["checkout_image"]["tmp_name"], $target_file)) {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            $_SESSION['message_type'] = "danger";
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $_SESSION['message'] = "กรุณาแนบรูปภาพ";
        $_SESSION['message_type'] = "danger";
        header('Location: dashboard.php');
        exit;
    }

    // --- 2. อัปเดตฐานข้อมูล (ไม่ต้องคำนวณสถานะ) ---
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
    $_SESSION['message'] = "การร้องขอไม่ถูกต้อง";
    $_SESSION['message_type'] = "danger";
}

$conn->close();
header('Location: dashboard.php');
exit;
?>