<?php
require_once '../config.php';

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  die('คุณไม่มีสิทธิ์เข้าถึง');
}

// 2. รับค่าวันที่
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d');

// 3. ตั้งชื่อไฟล์
$filename = "attendance_report_" . $start_date . "_to_" . $end_date . ".csv";

// 4. ตั้งค่า Headers สำหรับ Download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 5. เปิด stream ของ PHP output
$output = fopen('php://output', 'w');

// (สำคัญ) เพิ่ม BOM (Byte Order Mark) เพื่อให้ Excel เปิด UTF-8 (ภาษาไทย) ได้ถูกต้อง
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// 6. เขียนหัวตาราง (เหมือนใน PDF/attendance.php)
$headers = [
  'รหัสพนักงาน',
  'ชื่อ-นามสกุล',
  'วันที่',
  'กะการทำงาน',
  'เวลาเข้า (ตามกะ)',
  'เวลาออก (ตามกะ)',
  'เวลาเข้า (จริง)',
  'เวลาออก (จริง)',
  'สถานะ',
  'OT (ชม.)',
  'ผู้อนุมัติ'
];
fputcsv($output, $headers);

// 7. (*** แก้ไข SQL Query ***)
$sql = "SELECT 
            a.id as attendance_id, a.attendance_date, a.check_in_time, 
            a.check_in_status, a.check_out_time, a.approver_id,
            u.employee_id, u.prefix_th, u.name_th, u.lastname_th,
            s.name as schedule_name, s.time_in, s.time_out,
            approver.name_th as approver_name
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        
        -- (*** โค้ดใหม่: อัปเดตตรรกะการ JOIN กะ ***)
        LEFT JOIN employee_schedules es ON u.id = es.user_id 
            AND a.attendance_date BETWEEN es.start_date AND es.end_date
        LEFT JOIN schedule_types s ON es.schedule_id = s.id
        -- (*** จบโค้ดใหม่ ***)

        LEFT JOIN users approver ON a.approver_id = approver.id 
        WHERE a.attendance_date BETWEEN ? AND ?
        ORDER BY u.employee_id, a.attendance_date";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// 8. วนลูปและเขียนข้อมูลลง CSV
while ($row = $result->fetch_assoc()) {

  // --- (ตรรกะคำนวณสถานะและ OT - *** ไม่ต้องแก้ไข ***) ---
  // ตรรกะนี้ถูกต้องอยู่แล้ว เพราะอิงจาก $row['time_in'] และ $row['time_out']
  // ซึ่งตอนนี้ดึงมาจากตารางกะที่ถูกต้องแล้ว
  $status_text = 'N/A';
  if (!empty($row['check_in_time']) && !empty($row['time_in'])) {
    $check_in_timestamp = strtotime($row['check_in_time']);
    $schedule_in_timestamp = strtotime($row['attendance_date'] . ' ' . $row['time_in']);

    if ($check_in_timestamp < $schedule_in_timestamp) {
      $status_text = 'เข้าก่อนเวลา';
    } elseif ($row['check_in_status'] == 'late') {
      $status_text = 'มาสาย';
    } else {
      $status_text = 'ตรงเวลา';
    }
  }

  $ot_hours = 0;
  if (!empty($row['check_out_time']) && !empty($row['time_out'])) {
    $check_out_timestamp = strtotime($row['check_out_time']);
    $schedule_out_timestamp = strtotime($row['attendance_date'] . ' ' . $row['time_out']);
    $diff_seconds = $check_out_timestamp - $schedule_out_timestamp;
    if ($diff_seconds > 3600) {
      $ot_hours = floor($diff_seconds / 3600);
    }
  }
  // --- (จบตรรกะ) ---

  // 9. เตรียมข้อมูล 1 แถว (*** ไม่ต้องแก้ไข ***)
  $csv_line = [
    $row['employee_id'],
    $row['prefix_th'] . ' ' . $row['name_th'] . ' ' . $row['lastname_th'],
    $row['attendance_date'],
    $row['schedule_name'] ?? 'N/A', // (เพิ่ม ?? 'N/A' กัน Error ถ้าไม่มีกะ)
    $row['time_in'] ?? 'N/A',
    $row['time_out'] ?? 'N/A',
    $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '',
    $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '',
    $status_text,
    $ot_hours > 0 ? $ot_hours : '0',
    $row['approver_name'] ?? ''
  ];

  // 10. เขียนลงไฟล์
  fputcsv($output, $csv_line);
}

$stmt->close();
$conn->close();
fclose($output);
exit;
?>