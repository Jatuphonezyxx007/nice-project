<?php
require_once '../config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่าจากฟอร์ม
    $employee_id = $_POST['employee_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $position = $_POST['position'];
    $schedule_id = $_POST['schedule_id'];
    
    // --- 1. Hashing รหัสผ่าน (สำคัญมาก) ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // --- 2. จัดการการอัปโหลดรูปโปรไฟล์ (ถ้ามี) ---
    $profile_filename = NULL;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../assets/uploads/profiles/";
        $profile_filename = time() . '_' . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $profile_filename;
        
        if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // ถ้าอัปโหลดไม่สำเร็จ ก็ไม่เป็นไร ให้เป็น NULL ไป
            $profile_filename = NULL;
        }
    }

    // --- 3. บันทึกลงฐานข้อมูล ---
    // (เราตั้งค่า role เป็น 'employee' โดยอัตโนมัติ)
    $stmt = $conn->prepare("INSERT INTO users (employee_id, password, firstname, lastname, email, position, profile_image, schedule_id, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'employee')");
    
    $stmt->bind_param("sssssssi", 
        $employee_id, 
        $hashed_password, 
        $firstname, 
        $lastname, 
        $email, 
        $position, 
        $profile_filename, 
        $schedule_id
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "เพิ่มพนักงาน ($firstname) สำเร็จ!";
        $_SESSION['message_type'] = "success";
    } else {
        // ตรวจสอบ Error (เช่น employee_id หรือ email ซ้ำ)
        if ($conn->errno == 1062) { // 1062 คือ Error Code สำหรับ Duplicate entry
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
header('Location: employees.php'); // กลับไปหน้าจัดการพนักงาน
exit;
?>