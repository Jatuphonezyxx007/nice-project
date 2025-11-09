<?php
// api/api_update_shift.php
require_once '../config.php';
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์ Admin (เหมือนเดิม)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ']);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
  echo json_encode(['success' => false, 'message' => 'การร้องขอไม่ถูกต้อง']);
  exit;
}

// 2. (อัปเดต) รับค่าจาก Form (ตอนนี้เป็นแบบรายวัน)
$user_id = $_POST['user_id'] ?? null;
$shift_date = $_POST['shift_date'] ?? null; // (เปลี่ยนจาก start_date)
$new_schedule_id = (int) $_POST['schedule_id']; // 0 = ไม่กำหนดกะ (ลบ)
$admin_id = $_SESSION['user_id']; // ID ของ Admin ที่กำลังบันทึก

if (empty($user_id) || empty($shift_date)) {
  echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน (user_id หรือ shift_date)']);
  exit;
}

// 3. (อัปเดต) ตรรกะ: "ลบของเก่า แล้วเพิ่มของใหม่" (UPSERT)
// นี่จะทำให้เราสามารถ "ลบ" กะ (โดยส่ง schedule_id = 0) 
// หรือ "เปลี่ยน" กะ (โดยส่ง schedule_id ใหม่) ได้ในครั้งเดียว

$conn->begin_transaction();
try {
  // 3.1 ลบกะเดิมของคนนี้ ในวันนั้น
  $stmt_delete = $conn->prepare("DELETE FROM employee_schedules WHERE user_id = ? AND shift_date = ?");
  $stmt_delete->bind_param("is", $user_id, $shift_date);
  $stmt_delete->execute();
  $stmt_delete->close();

  // 3.2 ถ้ากะใหม่ไม่ใช่ "0" (ไม่กำหนดกะ) ให้เพิ่มเข้าไป
  if ($new_schedule_id > 0) {
    $stmt_insert = $conn->prepare(
      "INSERT INTO employee_schedules (user_id, schedule_id, shift_date, assigned_by) 
             VALUES (?, ?, ?, ?)"
    );
    $stmt_insert->bind_param("iisi", $user_id, $new_schedule_id, $shift_date, $admin_id);
    $stmt_insert->execute();
    $stmt_insert->close();
  }

  // 3.3 ถ้าทุกอย่างสำเร็จ
  $conn->commit();
  echo json_encode(['success' => true]);

} catch (Exception $e) {
  // 3.4 ถ้ามีอะไรพลาด
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => 'ล้มเหลวในการอัปเดต: ' . $e->getMessage()]);
}

$conn->close();
exit;
?>