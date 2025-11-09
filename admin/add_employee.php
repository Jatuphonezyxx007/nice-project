<?php
require_once '../config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // (อัปเดต) รับค่าใหม่ทั้งหมดตาม Schema
    $prefix_th = $_POST['prefix_th'];
    $name_th = $_POST['name_th'];
    $lastname_th = $_POST['lastname_th'];
    $prefix_en = $_POST['prefix_en'];
    $name_en = $_POST['name_en'];
    $lastname_en = $_POST['lastname_en'];
    $employee_id = $_POST['employee_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $position_id = (int) $_POST['position_id'];
    $schedule_id = (int) $_POST['schedule_id'];

    // Hashing รหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // จัดการรูปโปรไฟล์ (เหมือนเดิม)
    $profile_filename = NULL;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../assets/uploads/profiles/";

        // 1. ดึงนามสกุลไฟล์ (เช่น ".jpg", ".png")
        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);

        // 2. สร้างชื่อไฟล์ใหม่จาก รหัสพนักงาน + นามสกุลไฟล์
        $profile_filename = $employee_id . '.' . $file_extension;

        $target_file = $target_dir . $profile_filename;

        // 3. ย้ายไฟล์ (จะทับไฟล์เก่าที่มีชื่อเดียวกัน ถ้ามี)
        if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_filename = NULL; // ถ้าล้มเหลว ให้เป็น NULL
        }
    }

    // (อัปเดต) SQL INSERT ให้ตรงกับ Schema ใหม่
// (อัปเดต) SQL INSERT
    $sql = "INSERT INTO users (...) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'employee')";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssssssiis",
        $prefix_th,
        $name_th,
        $lastname_th,
        $prefix_en,
        $name_en,
        $lastname_en,
        $employee_id,
        $email,
        $hashed_password,
        $position_id,
        $schedule_id,
        $profile_filename // <-- ใช้ $profile_filename ที่สร้างใหม่
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "เพิ่มพนักงาน ($prefix_th $name_th) สำเร็จ!";
        $_SESSION['message_type'] = "success";
    } else {
        if ($conn->errno == 1062) {
            $_SESSION['message'] = "เกิดข้อผิดพลาด: รหัสพนักงาน หรือ Email นี้มีในระบบแล้ว";
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();

} else {
    $_SESSION['message'] = "การร้องขอไม่ถูกต้อง";
    $_SESSION['message_type'] = "danger";
}

$conn->close();
header('Location: employees.php');
exit;
?>