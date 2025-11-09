<?php
// 1. เริ่ม Session และเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config.php'; // (ต้องมี $conn หรือ $pdo)

// 2. ตรวจสอบว่า Login หรือยัง
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Authentication required']);
  exit;
}

// 3. ตั้งค่า Header
header('Content-Type: application/json');

// 4. รับค่าวันที่ (start/end) ที่ FullCalendar ส่งมา
// เราจะใช้ค่เเหล่านี้เพื่อกรองข้อมูล (Query เฉพาะที่จำเป็น)
$filter_start_date = $_GET['start'];
$filter_end_date = $_GET['end'];

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$events = [];

try {
  // 5. สร้าง SQL หลัก
  // ดึงข้อมูลกะที่ "คาบเกี่ยว" (overlap) กับช่วงวันที่ FullCalendar ร้องขอ
  $sql = "SELECT 
                ES.user_id, 
                ES.start_date, 
                ES.end_date, 
                ST.name AS schedule_name, 
                ST.time_in, 
                ST.time_out, 
                ST.color, 
                U.name_th,
                U.lastname_th
            FROM employee_schedules AS ES
            JOIN schedule_types AS ST ON ES.schedule_id = ST.id
            JOIN users AS U ON ES.user_id = U.id
            WHERE 
                ES.start_date <= ? AND ES.end_date >= ?"; // ตรรกะ Overlap

  $params = [$filter_end_date, $filter_start_date];
  $types = "ss";

  // 6. ใช้ Business Logic (Admin vs Employee)
  if ($role != 'admin') {
    $sql .= " AND ES.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
  }

  $stmt = $conn->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  // 7. (สำคัญ!) วนลูปขยายช่วงวันที่
  while ($row = $result->fetch_assoc()) {

    // สร้าง Title ของ Event
    $title = ($role == 'admin')
      ? $row['name_th'] . ' - ' . $row['schedule_name'] // Admin: "สมชาย - กะ 8AM"
      : $row['schedule_name']; // Employee: "กะ 8AM"

    // วนลูปสร้าง Event รายวันจาก "ช่วง" ที่ได้มา
    $current_date = new DateTime($row['start_date']);
    $period_end = new DateTime($row['end_date']);

    while ($current_date <= $period_end) {
      // (Optimization) กรองเฉพาะวันที่อยู่ใน view ของปฏิทินจริงๆ
      if ($current_date->format('Y-m-d') >= $filter_start_date && $current_date->format('Y-m-d') <= $filter_end_date) {

        $events[] = [
          'title' => $title,
          'resourceId' => $row['user_id'], // เชื่อม Event นี้กับพนักงาน
          'start' => $current_date->format('Y-m-d') . 'T' . $row['time_in'],
          'end' => $current_date->format('Y-m-d') . 'T' . $row['time_out'],
          'color' => $row['color']
        ];
      }
      // ไปวันถัดไป
      $current_date->modify('+1 day');
    }
  }

  $stmt->close();

  // 8. ส่งข้อมูล JSON กลับไป
  echo json_encode($events);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
?>