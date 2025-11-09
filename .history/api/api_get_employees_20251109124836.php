<?php
// 1. เรียก config (สำหรับ $conn และ session_start())
require_once '../config.php';

header('Content-Type: application/json');

// 2. (Security) ตรวจสอบว่า Login หรือยัง
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// 3. ดึงช่วงวันที่ ที่ FullCalendar ร้องขอ (เช่น '2025-09-22' ถึง '2025-10-06')
$start_date_str = $_GET['start'];
$end_date_str = $_GET['end'];

$events = [];

// 4. สร้าง SQL Query หลัก
// (ดึงข้อมูลกะ *ทั้งสัปดาห์* ที่คาบเกี่ยวกับช่วงวันที่ ที่ปฏิทินร้องขอ)
$sql = "SELECT es.user_id, es.schedule_id, es.start_date, es.end_date, 
               st.name, st.time_in, st.time_out, st.color
        FROM employee_schedules AS es
        JOIN schedule_types AS st ON es.schedule_id = st.id
        WHERE es.start_date < ? AND es.end_date > ?"; // (หาช่วงที่ Overlap)

// 5. เตรียม Parameters สำหรับ Prepared Statement
$params = [$end_date_str, $start_date_str];
$types = "ss";

// 6. (สำคัญ) กรองข้อมูลตามสิทธิ์
// ถ้าเป็น Employee ให้เห็นเฉพาะ user_id ของตัวเอง
if ($_SESSION['role'] == 'employee') {
  $sql .= " AND es.user_id = ?";
  $types .= "i"; // 'i' for integer
  $params[] = $_SESSION['user_id'];
}

// 7. รัน Query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {

    // 8. (สำคัญ) แปลงข้อมูลเป็น Recurring Event Format
    // FullCalendar ต้องการวันที่สิ้นสุด (endRecur) เป็นแบบ exclusive (คือ +1 วันจากข้อมูลจริง)
    $end_recur_date = new DateTime($row['end_date']);
    $end_recur_date->modify('+1 day');

    // 9. สร้าง Event Object
    $events[] = [
      'resourceId' => $row['user_id'], // (สำหรับ Admin View)
      'title' => htmlspecialchars($row['name']), // เช่น "กะ 8AM"
      'startTime' => $row['time_in'],    // เช่น "08:00:00"
      'endTime' => $row['time_out'],   // เช่น "17:00:00"
      'startRecur' => $row['start_date'], // วันที่เริ่มกะนี้ (เช่น "2025-09-22")
      'endRecur' => $end_recur_date->format('Y-m-d'), // วันที่สิ้นสุด (เช่น "2025-09-29")
      'backgroundColor' => $row['color'],
      'borderColor' => $row['color'],
      'extendedProps' => [ // ข้อมูลเพิ่มเติม (เผื่อใช้ในอนาคต)
        'schedule_id' => $row['schedule_id'],
        'time_range' => date('H:i', strtotime($row['time_in'])) . ' - ' . date('H:i', strtotime($row['time_out']))
      ]
    ];
  }
}

$stmt->close();
$conn->close();

// 10. ส่งข้อมูล JSON กลับไป
echo json_encode($events);
?>