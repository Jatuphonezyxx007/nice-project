<?php
// api/api_update_shift.php
require_once '../config.php';
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  echo json_encode(['success' => false, 'message' => 'การร้องขอไม่ถูกต้อง']);
  exit;
}

// 2. รับค่าจาก Form
$user_id = $_POST['user_id'] ?? null;
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;
$new_schedule_id = (int) $_POST['schedule_id']; // 0 = ไม่กำหนดกะ (ลบ)

if (empty($user_id) || empty($start_date) || empty($end_date)) {
  echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
  exit;
}

// 3. ตรรกะ:
// เราจะ "ลบ" กะเดิมในช่วงสัปดาห์นั้นก่อน แล้ว "เพิ่ม" กะใหม่เข้าไป
// (นี่คือวิธีที่ง่ายที่สุดในการจัดการกะรายสัปดาห์)

$conn->begin_transaction();
try {
  // 3.1 ลบกะเดิมในช่วงสัปดาห์นี้ของพนักงานคนนี้
  // (เราจะลบเฉพาะกะที่ "เริ่ม" ในสัปดาห์นี้)
  $stmt_delete = $conn->prepare("DELETE FROM employee_schedules WHERE user_id = ? AND start_date = ?");
  $stmt_delete->bind_param("is", $user_id, $start_date);
  $stmt_delete->execute();
  $stmt_delete->close();

  // 3.2 ถ้ากะใหม่ไม่ใช่ "0" (ไม่กำหนดกะ) ให้เพิ่มเข้าไป
  if ($new_schedule_id > 0) {
    $stmt_insert = $conn->prepare("INSERT INTO employee_schedules (user_id, schedule_id, start_date, end_date) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $user_id, $new_schedule_id, $start_date, $end_date);
    $stmt_insert->execute();
    $stmt_insert->close();
  }

  // 3.3 ถ้าทุกอย่างสำเร็จ
  $conn->commit();
  echo json_encode(['success' => true]);

} catch (Exception $e) {
  // 3.4 ถ้ามีอะไรพลาด
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => 'ล้มเหลวในการอัปเดตฐานข้อมูล: ' . $e->getMessage()]);
}

$conn->close();
exit;
?>