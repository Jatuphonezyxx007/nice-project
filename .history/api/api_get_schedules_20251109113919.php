<?php
// api_get_schedules.php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// 1. รับค่าวันที่ (เหมือนเดิม)
$start_date = $_GET['start'];
$end_date = $_GET['end'];
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// 2. (อัปเดต SQL) เปลี่ยนไปดึงจากตารางใหม่
$sql = "SELECT 
            es.user_id, 
            es.schedule_id, -- (*** เพิ่มบรรทัดนี้ ***)
            es.shift_date,
            s.name as schedule_name,
            s.time_in,
            s.time_out,
            s.color as schedule_color
        FROM employee_schedules es
        JOIN schedule_types s ON es.schedule_id = s.id
        WHERE 
            es.shift_date BETWEEN ? AND ?
        ";

$params = [$start_date, $end_date];
$param_types = "ss";

// 3. ตรรกะ Admin/Employee (เหมือนเดิม)
if ($current_user_role != 'admin') {
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

  // 4. (อัปเดต) แปลงข้อมูลเป็น Event Format
  $events[] = [
    // 'title' คือข้อความในช่องกะ
    'title' => $row['schedule_name'],
    'start' => $row['shift_date'], // กะนี้สำหรับวันที่นี้
    'allDay' => true,
    'backgroundColor' => $row['schedule_color'] ?? '#3788d8',
    'borderColor' => $row['schedule_color'] ?? '#3788d8',

    // (*** สำคัญมาก ***)
    // 'resourceId' คือ ID พนักงาน -> บอกปฏิทินว่ากะนี้เป็นของ "แถว" ไหน
    'resourceId' => $row['user_id'],

    // (ข้อมูลแอบไว้ให้ Admin ใช้)
    'extendedProps' => [
      'schedule_id' => $row['schedule_id'],
      'time_range' => date('H:i', strtotime($row['time_in'])) . ' - ' . date('H:i', strtotime($row['time_out']))
    ]
  ];
}

$stmt->close();
$conn->close();
echo json_encode($events);
exit;
?>