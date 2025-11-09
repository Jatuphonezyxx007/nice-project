<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // ใส่รหัสผ่านของคุณ
define('DB_NAME', 'attendance_system');

// --- (ส่วนที่ 1: การตั้งค่าความปลอดภัย) ---

// 1. (ป้องกัน Brute Force) ตั้งค่าการจำกัดการ Login
define('MAX_LOGIN_ATTEMPTS', 5); // ล็อกหลังจากพยายามผิด 5 ครั้ง
define('LOGIN_TIME_PERIOD', 10); // ภายใน 15 นาที (900 วินาที)

// 2. (ป้องกัน Session Hijacking & CSRF) ตั้งค่า Session ให้ปลอดภัยสูงสุด
// สั่งให้ PHP ใช้ Cookie ที่ปลอดภัยเท่านั้น
ini_set('session.use_only_cookies', 1);
// (ป้องกัน XSS) ห้าม JavaScript อ่านค่า Cookie (HttpOnly)
ini_set('session.cookie_httponly', 1);
// (ป้องกัน CSRF) จำกัด Cookie ให้ส่งเฉพาะโดเมนเรา
ini_set('session.cookie_samesite', 'Strict');

// (*** สำคัญมาก ***)
// ถ้าเว็บของคุณเป็น HTTPS (มี SSL) ให้เปิดบรรทัดนี้:
// ini_set('session.cookie_secure', 1);
// (ถ้าคุณยังใช้ http://localhost อย่าเพิ่งเปิด บรรทัดนี้ เพราะจะทำให้ Login ไม่ได้)

// --- (ส่วนที่ 2: เชื่อมต่อ DB และเริ่ม Session) ---

// เริ่ม Session "หลังจาก" ตั้งค่า ini_set แล้ว
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Bangkok');
define('BASE_URL', '/nice'); // (แก้ Path ของคุณให้ถูกต้อง)
?>