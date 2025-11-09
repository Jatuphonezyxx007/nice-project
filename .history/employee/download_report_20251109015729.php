<?php
// 1. (สำคัญมาก) เรียกใช้ Autoloader ของ Composer
require_once '../vendor/autoload.php';
require_once '../config.php'; // เรียกใช้ config

// 2. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
  die('กรุณาล็อกอินก่อน');
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name']; // (จากที่เราเก็บไว้ตอน login)

// 3. รับค่าวันที่
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');

// 4. ดึงข้อมูล (เหมือนไฟล์ attendance.php)
$sql = "SELECT a.*, 
               s.name as schedule_name, s.time_in, s.time_out
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN schedule_types s ON u.schedule_id = s.id
        WHERE a.user_id = ? 
          AND a.attendance_date BETWEEN ? AND ?
        ORDER BY a.attendance_date ASC"; // (เปลี่ยนเป็น ASC เพื่อให้เรียงจากเก่าไปใหม่)

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// 5. (สำคัญ) เริ่มสร้าง HTML ที่จะใส่ใน PDF
$html = "
<html>
<head>
<style>
    /* * mPDF จะใช้ Font ที่รองรับภาษาไทยอัตโนมัติ (เช่น Garuda หรือ Sarabun) 
     * ถ้าเรากำหนด font-family ที่รองรับไว้ (เช่น Noto Sans Thai Looped)
     */
    body { 
        font-family: 'Noto Sans Thai Looped', 'garuda', sans-serif; 
        font-size: 10pt;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    .text-center { text-align: center; }
    .text-danger { color: #dc3545; }
    .text-success { color: #198754; }
    .text-warning { color: #ffc107; }
    .text-info { color: #0dcaf0; }
</style>
</head>
<body>
    <h1>รายงานการลงเวลา</h1>
    <p><strong>พนักงาน:</strong> " . htmlspecialchars($user_name) . "</p>
    <p><strong>ช่วงวันที่:</strong> " . date('d/m/Y', strtotime($start_date)) . " ถึง " . date('d/m/Y', strtotime($end_date)) . "</p>
    
    <table>
        <thead>
            <tr>
                <th>วันที่</th>
                <th>กะการทำงาน</th>
                <th>เวลาเข้า (จริง)</th>
                <th>สถานะ</th>
                <th>เวลาออก (จริง)</th>
                <th>OT (ชม.)</th>
            </tr>
        </thead>
        <tbody>
";

// 6. วนลูปเพื่อสร้างแถวในตาราง
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {

    // (ตรรกะคำนวณสถานะและ OT - เหมือนเดิม)
    $status_text = 'N/A';
    $status_class = '';
    if (!empty($row['check_in_time']) && !empty($row['time_in'])) {
      $check_in_timestamp = strtotime($row['check_in_time']);
      $schedule_in_timestamp = strtotime($row['attendance_date'] . ' ' . $row['time_in']);
      if ($check_in_timestamp < $schedule_in_timestamp) {
        $status_text = 'เข้าก่อนเวลา';
        $status_class = 'text-info';
      } elseif ($row['check_in_status'] == 'late') {
        $status_text = 'มาสาย';
        $status_class = 'text-warning';
      } else {
        $status_text = 'ตรงเวลา';
        $status_class = 'text-success';
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

    // เพิ่มแถว HTML
    $html .= "
            <tr>
                <td>" . date('d/m/Y', strtotime($row['attendance_date'])) . "</td>
                <td>" . htmlspecialchars($row['schedule_name'] ?? 'N/A') . "</td>
                <td>" . ($row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '-') . "</td>
                <td class='" . $status_class . "'>" . $status_text . "</td>
                <td>" . ($row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '-') . "</td>
                <td class='text-danger'>" . ($ot_hours > 0 ? $ot_hours . ' ชม.' : '-') . "</td>
            </tr>
        ";
  }
} else {
  $html .= "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูล</td></tr>";
}

$html .= "
        </tbody>
    </table>
</body>
</html>
";

$stmt->close();
$conn->close();

// 7. (สำคัญ) สั่ง mPDF ให้ทำงาน
try {
  // กำหนดค่าให้ mPDF รู้จักภาษาไทย
  $mpdf = new \Mpdf\Mpdf([
    'autoLangToFont' => true,
    'fontDir' => array_merge(
      (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
      [
        // ถ้าคุณมีไฟล์ .ttf ของ 'Noto Sans Thai Looped' 
        // คุณสามารถใส่ path โฟลเดอร์ตรงนี้ได้
      ]
    ),
    'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
      // ตั้งค่า font fallback
      'notosansthailooped' => [
        'R' => 'Garuda.ttf', // ใช้ Garuda ที่ mPDF มักจะมีมาให้
        'I' => 'Garuda-Oblique.ttf',
      ]
    ],
    'default_font' => 'notosansthailooped' // บังคับใช้ font นี้
  ]);

  $mpdf->WriteHTML($html);

  // 8. สั่งให้ดาวน์โหลด
  $filename = "report_" . $user_id . "_" . $start_date . "_to_" . $end_date . ".pdf";
  $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);

} catch (\Mpdf\MpdfException $e) {
  echo "เกิดข้อผิดพลาดในการสร้าง PDF: " . $e->getMessage();
}

exit;
?>