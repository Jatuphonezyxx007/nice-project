<?php
require_once '../config.php';

// ตรวจสอบสิทธิ์ Admin (ต้อง Login และเป็น Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    $_SESSION['message_type'] = "danger";
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// 1. ตรวจสอบว่ามี ID ส่งมาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ไม่พบ ID พนักงาน";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}

$id = (int)$_GET['id'];

// 2. ป้องกันการลบตัวเอง
if ($id == $_SESSION['user_id']) {
    $_SESSION['message'] = "คุณไม่สามารถลบตัวเองได้";
    $_SESSION['message_type'] = "warning";
    header('Location: employees.php');
    exit;
}

// 3. (สำคัญ) ค้นหาข้อมูลพนักงานก่อน เพื่อเอารูปภาพมาลบ และตรวจสอบว่าเป็น Admin หรือไม่
$stmt_select = $conn->prepare("SELECT role, profile_image FROM users WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 0) {
    $_SESSION['message'] = "ไม่พบข้อมูลพนักงาน ID: $id";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}

$user = $result->fetch_assoc();
$stmt_select->close();

// 4. ป้องกันการลบ Admin คนอื่น (อนุญาตให้ลบได้เฉพาะ 'employee')
if ($user['role'] == 'admin') {
    $_SESSION['message'] = "ไม่สามารถลบผู้ใช้งานที่เป็น Admin ได้";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}

// 5. ดำเนินการลบข้อมูล
$stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    // 6. ลบรูปโปรไฟล์ (ถ้ามี)
    if (!empty($user['profile_image'])) {
        $image_path = '../assets/uploads/profiles/' . $user['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path); // ลบไฟล์รูป
        }
    }
    
    $_SESSION['message'] = "ลบพนักงาน ID: $id สำเร็จแล้ว";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

$stmt_delete->close();
$conn->close();
header('Location: employees.php');
exit;
?>