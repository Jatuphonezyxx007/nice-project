<?php
// 1. เริ่ม Session และเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config.php'; // (ต้องมี $conn หรือ $pdo)

// 2. ตรวจสอบว่า Login หรือยัง (ป้องกันการเข้าถึง API โดยตรง)
if (!isset($_SESSION['user_id'])) {
  http_response_code(403); // Forbidden
  echo json_encode(['error' => 'Authentication required']);
  exit;
}

// 3. ตั้งค่า Header
header('Content-Type: application/json');

$resources = [];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
  // ใช้ $conn (MySQLi) ที่มาจาก config.php
  if ($role == 'admin') {
    // 4. ถ้าเป็น Admin: ดึงพนักงานทุกคน (หรือเฉพาะ role 'employee')
    // เราจะดึงทุกคนที่มีกะ (Join จาก employee_schedules) เพื่อไม่ให้รก
    // หรือดึงทุกคนที่เป็น 'employee' ก็ได้
    $sql = "SELECT id, CONCAT(name_th, ' ', lastname_th) as title 
                FROM users 
                WHERE role = 'employee'";
    $stmt = $conn->prepare($sql);

  } else {
    // 5. ถ้าเป็น Employee: ดึงเฉพาะตัวเอง
    $sql = "SELECT id, CONCAT(name_th, ' ', lastname_th) as title 
                FROM users 
                WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
  }

  $stmt->close();

  // 6. ส่งข้อมูล JSON กลับไป
  echo json_encode($resources);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>