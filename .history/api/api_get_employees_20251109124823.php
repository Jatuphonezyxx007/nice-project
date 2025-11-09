<?php
// 1. เรียก config (สำหรับ $conn และ session_start())
require_once '../config.php';

header('Content-Type: application/json');

// 2. (Security) ตรวจสอบให้แน่ใจว่านี่คือ Admin
// (ไฟล์ header.php ของคุณจะตั้งค่า $_SESSION['role'] ไว้แล้ว)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$resources = [];
$sql = "SELECT id, name_th, lastname_th, profile_image FROM users WHERE role = 'employee' ORDER BY id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {

    // 3. จัดการ URL รูปภาพ
    $image_url = null;
    if (!empty($row['profile_image'])) {
      // (ใช้ BASE_URL จาก config.php เพื่อให้ URL ถูกต้องเสมอ)
      $image_url = BASE_URL . '/assets/uploads/profiles/' . htmlspecialchars($row['profile_image']);
    }

    // 4. แปลงข้อมูลเป็น Format ที่ FullCalendar (Resources) ต้องการ
    $resources[] = [
      'id' => $row['id'],
      'title' => htmlspecialchars($row['name_th'] . ' ' . $row['lastname_th']),
      'image_url' => $image_url // ส่ง null ถ้าไม่มีรูป
    ];
  }
}

// 5. ส่งข้อมูล JSON กลับไป
echo json_encode($resources);

$conn->close();
?>