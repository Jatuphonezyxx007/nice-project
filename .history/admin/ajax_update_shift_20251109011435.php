<?php
require_once '../config.php';

// ตั้งค่า Header ให้รู้ว่าเป็น JSON
header('Content-Type: application/json');

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
  exit;
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  echo json_encode(['success' => false, 'message' => 'การร้องขอไม่ถูกต้อง']);
  exit;
}

// รับค่า
$user_id = $_POST['user_id'] ?? null;
$schedule_id = $_POST['schedule_id'] ?? null;

if (empty($user_id) || empty($schedule_id)) {
  echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
  exit;
}

// อัปเดตฐานข้อมูล
$stmt = $conn->prepare("UPDATE users SET schedule_id = ? WHERE id = ?");
$stmt->bind_param("ii", $schedule_id, $user_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'ล้มเหลวในการอัปเดตฐานข้อมูล: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>