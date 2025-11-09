<?php
// _create_user.php
// สคริปต์สำหรับสร้างผู้ใช้ใหม่ (สำหรับทดสอบ)

require_once 'config.php'; // เรียกใช้การตั้งค่าฐานข้อมูล

echo "<h1>สคริปต์สร้างผู้ใช้</h1>";
echo "<hr>";

/*
 * ===========================================
 * สร้างผู้ใช้ ADMIN ใหม่
 * ===========================================
 */
$admin_id = 'admin'; // <--- ตั้งชื่อ username ที่คุณต้องการ
$admin_pass = 'adminpass'; // <--- ตั้งรหัสผ่านที่คุณต้องการ
$admin_email = 'admin2@example.com';

// Hashing รหัสผ่าน (สำคัญมาก)
$admin_hash = password_hash($admin_pass, PASSWORD_DEFAULT);

// ใช้ Prepared Statement เพื่อความปลอดภัย
$stmt_admin = $conn->prepare("INSERT INTO users 
    (employee_id, password, firstname, lastname, email, schedule_id, role) 
    VALUES (?, ?, 'แอดมิน', 'ใหม่', ?, 1, 'admin')");
// เราใช้ schedule_id = 1 (กะปกติ) ที่เราสร้างไว้ใน SQL

$stmt_admin->bind_param("sss", $admin_id, $admin_hash, $admin_email);

if ($stmt_admin->execute()) {
    echo "<p style='color:green; font-size: 1.2rem;'>
        <b>สำเร็จ:</b> สร้างผู้ใช้ ADMIN ใหม่เรียบร้อยแล้ว <br>
        <b>Username:</b> {$admin_id} <br>
        <b>Password:</b> {$admin_pass}
    </p>";
} else {
    echo "<p style='color:red; font-size: 1.2rem;'>
        <b>ผิดพลาด (Admin):</b> " . htmlspecialchars($stmt_admin->error) . "
    </p>";
}
$stmt_admin->close();

echo "<hr>";

/*
 * ===========================================
 * สร้างผู้ใช้ EMPLOYEE ใหม่
 * ===========================================
 */
$emp_id = 'EMP999'; // <--- ตั้งรหัสพนักงาน
$emp_pass = 'emppass'; // <--- ตั้งรหัสผ่าน
$emp_email = 'emp999@example.com';

// Hashing รหัสผ่าน
$emp_hash = password_hash($emp_pass, PASSWORD_DEFAULT);

$stmt_emp = $conn->prepare("INSERT INTO users 
    (employee_id, password, firstname, lastname, email, schedule_id, role) 
    VALUES (?, ?, 'พนักงาน', 'ใหม่', ?, 1, 'employee')");

$stmt_emp->bind_param("sss", $emp_id, $emp_hash, $emp_email);

if ($stmt_emp->execute()) {
    echo "<p style='color:blue; font-size: 1.2rem;'>
        <b>สำเร็จ:</b> สร้างผู้ใช้ EMPLOYEE ใหม่เรียบร้อยแล้ว <br>
        <b>Username:</b> {$emp_id} <br>
        <b>Password:</b> {$emp_pass}
    </p>";
} else {
    echo "<p style='color:red; font-size: 1.2rem;'>
        <b>ผิดพลาด (Employee):</b> " . htmlspecialchars($stmt_emp->error) . "
    </p>";
}
$stmt_emp->close();
$conn->close();

echo "<hr>";
echo "<h2 style='color:red;'>🚨 คำเตือนด้านความปลอดภัย 🚨</h2>";
echo "<h3 style='font-weight:bold;'>กรุณาลบไฟล์นี้ (<code>_create_user.php</code>) ทันทีหลังจากใช้งานเสร็จสิ้น!</h3>";

?>