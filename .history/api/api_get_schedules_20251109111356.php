<?php
// api_get_schedules.php
require_once '../config.php'; // (ต้อง session_start() ใน config.php)

// 1. ตรวจสอบการ Login (จำเป็นมาก!)
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// 2. FullCalendar จะส่งวันที่เริ่มต้น (start) และสิ้นสุด (end) ของเดือนที่ดูมาให้
$start_date = $_GET['start'];
$end_date = $_GET['end'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// 3. เตรียม SQL
$sql = "SELECT 
            es.start_date, 
            es.end_date,
            u.id as user_id,  /* (*** เพิ่มบรรทัดนี้ ***) */
            u.name_th, 
            u.lastname_th,
            s.name as schedule_name,
            s.color as schedule_color
        FROM employee_schedules es
        JOIN users u ON es.user_id = u.id
        JOIN schedule_types s ON es.schedule_id = s.id
        WHERE 
            s.id > 0 AND (
                (es.start_date <= ? AND es.end_date >= ?) 
            )";

$params = [$end_date, $start_date];
$param_types = "ss";

// 4. (*** สำคัญ ***) ตรรกะ Admin / Employee
if ($current_user_role == 'admin') {
  // Admin: ดูได้ทุกคน
  $sql .= " ORDER BY u.name_th";
} else {
  // Employee: ดูได้แค่ตัวเอง
  $sql .= " AND es.user_id = ?";
  $params[] = $current_user_id;
  $param_types .= "i";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {

  // 5. แปลงข้อมูลเป็น Format ของ FullCalendar
  $events[] = [
    'title' => $row['name_th'] . ' ' . $row['lastname_th'] . ': ' . $row['schedule_name'],
    'start' => $row['start_date'],
    'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')), // FullCalendar ต้องการ end date + 1 วัน
    'backgroundColor' => $row['schedule_color'] ?? '#3788d8', // (ดึงสีมาจากตารางกะ)
    'borderColor' => $row['schedule_color'] ?? '#3788d8',
    'allDay' => true,
    // (*** ใหม่ ***) ส่งข้อมูลแอบไว้ให้ Admin ใช้ตอนแก้ไข
    'extendedProps' => [
      'user_id' => $row['user_id'], // ID พนักงาน
      'schedule_start' => $row['start_date'] // วันเริ่มกะ (สำหรับอ้างอิง)
    ]
  ];
}

$stmt->close();
$conn->close();

// 6. ส่งข้อมูลเป็น JSON
header('Content-Type: application/json');
echo json_encode($events);
exit;
?>