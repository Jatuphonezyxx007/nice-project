<?php
require_once '../config.php';

// ตรวจสอบว่า login หรือยัง
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ตรวจสอบว่าเป็นการส่งแบบ POST และมีไฟล์มาด้วย
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['checkin_image'])) {
    
    $user_id = $_SESSION['user_id'];
    $filename = '';

    // --- 1. จัดการการอัปโหลดไฟล์ ---
    if ($_FILES['checkin_image']['error'] == 0) {
        $target_dir = "../assets/uploads/attendance/";
        // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน
        $filename = $user_id . '_' . time() . '_' . basename($_FILES["checkin_image"]["name"]);
        $target_file = $target_dir . $filename;

        // (ควรมีการตรวจสอบประเภทไฟล์และขนาดไฟล์ที่นี่)
        
        if (!move_uploaded_file($_FILES["checkin_image"]["tmp_name"], $target_file)) {
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

    // --- 2. คำนวณสถานะ (สาย/ตรงเวลา) ---
    $status = 'on_time'; // ค่าเริ่มต้น
    $stmt_schedule = $conn->prepare("SELECT s.time_in FROM users u JOIN schedule_types s ON u.schedule_id = s.id WHERE u.id = ?");
    $stmt_schedule->bind_param("i", $user_id);
    $stmt_schedule->execute();
    $result_schedule = $stmt_schedule->get_result();
    
    if ($result_schedule->num_rows > 0) {
        $schedule = $result_schedule->fetch_assoc();
        $time_in_schedule = strtotime($schedule['time_in']);
        $current_time = strtotime(date('H:i:s')); // เวลาปัจจุบัน

        if ($current_time > $time_in_schedule) {
            $status = 'late';
        }
    }
    $stmt_schedule->close();

    // --- 3. บันทึกลงฐานข้อมูล ---
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
    $_SESSION['message'] = "การร้องขอไม่ถูกต้อง";
    $_SESSION['message_type'] = "danger";
}

$conn->close();
header('Location: dashboard.php');
exit;
?>