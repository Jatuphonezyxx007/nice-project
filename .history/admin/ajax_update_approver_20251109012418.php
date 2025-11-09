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
$attendance_id = $_POST['attendance_id'] ?? null;
// เราอาจจะส่งค่า "ว่าง" มา เพื่อยกเลิกการอนุมัติ
$approver_id = $_POST['approver_id'] ?? null;
if (empty($approver_id)) {
  $approver_id = null; // ตั้งเป็น NULL ถ้าค่าที่ส่งมาว่าง
}

if (empty($attendance_id)) {
  echo json_encode(['success' => false, 'message' => 'ไม่พบ ID การลงเวลา']);
  exit;
}

// อัปเดตฐานข้อมูล
$stmt = $conn->prepare("UPDATE attendance SET approver_id = ? WHERE id = ?");
$stmt->bind_param("ii", $approver_id, $attendance_id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'ล้มเหลวในการอัปเดต: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>