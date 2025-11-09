<?php
require_once '../config.php';
require_once '../includes/header.php'; // เรียก Header

// 1. ดึง ID ของพนักงานที่ล็อกอินอยู่
$user_id = $_SESSION['user_id'];

// 2. ตรรกะการกรอง (Filter)
// ตั้งค่าวันเริ่มต้นและสิ้นสุด (ถ้าไม่ได้เลือก ให้เป็นเดือนนี้)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// 3. ดึงข้อมูลการลงเวลาของพนักงานคนนี้
$sql = "SELECT a.*, 
               s.name as schedule_name, s.time_in, s.time_out
        FROM attendance a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN schedule_types s ON u.schedule_id = s.id
        WHERE a.user_id = ? 
          AND a.attendance_date BETWEEN ? AND ?
        ORDER BY a.attendance_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$attendance_result = $stmt->get_result();
?>

<title>ประวัติการลงเวลา</title>

<div class="container">
  <h3><i class="bi bi-calendar-check me-2"></i> ประวัติการลงเวลาของคุณ</h3>
  <hr>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="attendance.php" class="row g-3 align-items-end mb-3">
        <div class="col-md-4">
          <label for="start_date_get" class="form-label">วันที่เริ่มต้น:</label>
          <input type="date" class="form-control" id="start_date_get" name="start_date"
            value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="col-md-4">
          <label for="end_date_get" class="form-label">วันที่สิ้นสุด:</label>
          <input type="date" class="form-control" id="end_date_get" name="end_date"
            value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> ดูประวัติ</button>
        </div>
      </form>

      <hr>

      <form method="POST" action="download_report.php" target="_blank">
        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        <button type="submit" class="btn btn-success w-100">
          <i class="bi bi-file-earmark-pdf-fill me-2"></i> ดาวน์โหลดรายงาน PDF (สำหรับช่วงวันที่ด้านบน)
        </button>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0">ข้อมูลการลงเวลา (<?php echo date('d M Y', strtotime($start_date)); ?> -
        <?php echo date('d M Y', strtotime($end_date)); ?>)</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
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
            <?php if ($attendance_result->num_rows > 0): ?>
              <?php while ($row = $attendance_result->fetch_assoc()): ?>
                <?php
                // (ตรรกะคำนวณสถานะและ OT)
                $status_text = 'N/A';
                $status_bg = 'secondary';
                if (!empty($row['check_in_time']) && !empty($row['time_in'])) {
                  $check_in_timestamp = strtotime($row['check_in_time']);
                  $schedule_in_timestamp = strtotime($row['attendance_date'] . ' ' . $row['time_in']);
                  if ($check_in_timestamp < $schedule_in_timestamp) {
                    $status_text = 'เข้าก่อนเวลา';
                    $status_bg = 'info';
                  } elseif ($row['check_in_status'] == 'late') {
                    $status_text = 'มาสาย';
                    $status_bg = 'warning';
                  } else {
                    $status_text = 'ตรงเวลา';
                    $status_bg = 'success';
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
                ?>
                <tr>
                  <td><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></td>
                  <td><?php echo htmlspecialchars($row['schedule_name'] ?? 'N/A'); ?></td>
                  <td><?php echo $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '-'; ?></td>
                  <td>
                    <span class="badge bg-<?php echo $status_bg; ?>">
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                  <td><?php echo $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '-'; ?></td>
                  <td>
                    <strong class="text-danger"><?php echo $ot_hours > 0 ? $ot_hours . ' ชม.' : '-'; ?></strong>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center text-muted">ไม่พบข้อมูลการลงเวลาในช่วงวันที่นี้</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$stmt->close();
$conn->close();
require_once '../includes/footer.php'; // เรียก Footer
?>