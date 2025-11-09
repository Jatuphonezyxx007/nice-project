<?php
require_once '../config.php';

// 1. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
  die('คุณไม่มีสิทธิ์เข้าถึง');
}

// 2. รับค่าวันที่
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d');

// 3. (*** แก้ไข ***) ตั้งชื่อไฟล์เป็น .xls
$filename = "attendance_report_" . $start_date . "_to_" . $end_date . ".xls";

// 4. (*** แก้ไข ***) ตั้งค่า Headers เป็น application/vnd.ms-excel
// นี่จะบอก Browser ว่าไฟล์นี้คือ Excel (แม้ข้างในจะเป็น HTML)
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 5. (*** แก้ไข ***) ลบ fopen, fputcsv และ BOM ทิ้ง
// เราจะใช้ 'echo' เพื่อพิมพ์ HTML ออกไปตรงๆ

// 6. เขียนหัวตาราง (HTML)
echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">';
echo '<title>Attendance Report</title>';
// (*** ใหม่ ***) เพิ่มสไตล์สำหรับตารางและสี
echo '<style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dddddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .bg-green { background-color: #d4edda; } /* เขียว (OT) */
        .bg-orange { background-color: #fff3cd; } /* ส้ม (Late) */
      </style>';
echo '</head><body>';
echo '<table><thead><tr>';

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
foreach ($headers as $header) {
  echo '<th>' . htmlspecialchars($header) . '</th>';
}
echo '</tr></thead><tbody>';

// 7. (*** ไม่แก้ไข ***) SQL Query ยังคงเดิม
$sql = "SELECT 
            a.id as attendance_id, a.attendance_date, a.check_in_time, 
            a.check_in_status, a.check_out_time, a.approver_id,
            u.employee_id, u.prefix_th, u.name_th, u.lastname_th,
            s.name as schedule_name, s.time_in, s.time_out,
            approver.name_th as approver_name
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN employee_schedules es ON u.id = es.user_id 
            AND a.attendance_date BETWEEN es.start_date AND es.end_date
        LEFT JOIN schedule_types s ON es.schedule_id = s.id
        LEFT JOIN users approver ON a.approver_id = approver.id 
        WHERE a.attendance_date BETWEEN ? AND ?
        ORDER BY u.employee_id, a.attendance_date";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// 8. (*** ไม่แก้ไข ***) ตรรกะคำนวณยังคงเดิม
while ($row = $result->fetch_assoc()) {
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

  // 9. (*** ไม่แก้ไข ***) เตรียมข้อมูล 1 แถว
  $csv_line = [
    $row['employee_id'],
    $row['prefix_th'] . ' ' . $row['name_th'] . ' ' . $row['lastname_th'],
    $row['attendance_date'],
    $row['schedule_name'] ?? 'N/A',
    $row['time_in'] ?? 'N/A',
    $row['time_out'] ?? 'N/A',
    $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '',
    $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '',
    $status_text,
    $ot_hours > 0 ? $ot_hours : '0',
    $row['approver_name'] ?? ''
  ];

  // 10. (*** ใหม่ ***) กำหนด Class สีตามเงื่อนไข
  $row_class = '';
  if ($ot_hours > 0) {
    $row_class = 'bg-green';
  } elseif ($status_text == 'มาสาย') {
    $row_class = 'bg-orange';
  }

  // 11. (*** แก้ไข ***) เขียนลงไฟล์เป็น <tr> และ <td>
  echo '<tr class="' . $row_class . '">';
  foreach ($csv_line as $cell) {
    // ใช้ mso-number-format:'\@' เพื่อบังคับให้ Excel มองข้อมูลเป็น Text
    // (ป้องกันปัญหารหัสพนักงาน 001 กลายเป็น 1)
    echo '<td style="mso-number-format:\'@\';">' . htmlspecialchars($cell) . '</td>';
  }
  echo '</tr>';
}

// (*** แก้ไข ***) ปิดตารางและ HTML
echo '</tbody></table></body></html>';

$stmt->close();
$conn->close();
// (*** แก้ไข ***) ลบ fclose
exit;
?>