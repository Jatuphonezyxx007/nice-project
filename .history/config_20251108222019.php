<?php
// config.php
// !!! กรุณาแก้ไขข้อมูลการเชื่อมต่อให้ตรงกับของคุณ !!!
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // ใส่รหัสผ่านของคุณ
define('DB_NAME', 'attendance_system');

// สร้างการเชื่อมต่อ (MySQLi)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า Charset เป็น UTF-8
$conn->set_charset("utf8mb4");

// ตั้งค่า Timezone (สำคัญมากสำหรับเวลา)
date_default_timezone_set('Asia/Bangkok');

// เริ่ม Session เสมอเมื่อเรียกใช้ config
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL (แก้ให้ตรงกับ path ของคุณ)
define('BASE_URL', '/attendance-system'); 
?>