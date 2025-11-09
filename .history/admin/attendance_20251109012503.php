<!-- <?php
require_once '../config.php';
require_once '../includes/header.php'; // เรียก Header

// 1. ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
  header('Location: ' . BASE_URL . '/employee/dashboard.php');
  exit;
}

// 2. ตรรกะการกรอง (Filter)
// ถ้าไม่ได้เลือกวัน, ให้แสดงวันนี้
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');

// 3. ดึงข้อมูลกะทั้งหมด (สำหรับ Modal แก้ไข)
$schedules_result = $conn->query("SELECT * FROM schedule_types");
$all_schedules = [];
while ($row = $schedules_result->fetch_assoc()) {
  $all_schedules[] = $row;
}

// 4. ดึงข้อมูลการลงเวลาทั้งหมด
$sql = "SELECT a.id as attendance_id, a.attendance_date, a.check_in_time, a.check_in_image, 
               a.check_in_status, a.check_out_time, a.check_out_image,
               u.id as user_id, u.prefix_th, u.name_th, u.lastname_th, u.employee_id,
               u.schedule_id as current_user_schedule_id, 
               s.name as current_schedule_name
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN schedule_types s ON u.schedule_id = s.id
        WHERE a.attendance_date = ?
        ORDER BY a.check_in_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$attendance_result = $stmt->get_result();
?>

<title>ดูบันทึกการลงเวลาทั้งหมด</title>

<div class="container">
  <h3><i class="bi bi-calendar-check me-2"></i> บันทึกการลงเวลาทั้งหมด</h3>
  <hr>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="attendance.php" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label for="filter_date" class="form-label">เลือกวันที่:</label>
          <input type="date" class="form-control" id="filter_date" name="filter_date"
            value="<?php echo htmlspecialchars($filter_date); ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> กรอง</button>
          <a href="attendance.php" class="btn btn-outline-secondary">วันนี้</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0">ข้อมูลการลงเวลา วันที่ <?php echo date('d F Y', strtotime($filter_date)); ?></h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>พนักงาน</th>
              <th>เวลาเข้า</th>
              <th>ภาพ (เข้า)</th>
              <th>สถานะ</th>
              <th>เวลาออก</th>
              <th>ภาพ (ออก)</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($attendance_result->num_rows > 0): ?>
              <?php while ($row = $attendance_result->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars($row['prefix_th'] . ' ' . $row['name_th']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($row['employee_id']); ?></small>
                  </td>
                  <td><?php echo $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '-'; ?></td>
                  <td>
                    <img
                      src="<?php echo BASE_URL . '/assets/uploads/attendance/' . ($row['check_in_image'] ? $row['check_in_image'] : 'default-check.png'); ?>"
                      alt="Check-in" width="50" height="50" class="img-thumbnail" style="object-fit: cover;">
                  </td>
                  <td>
                    <span class="badge bg-<?php echo $row['check_in_status'] == 'late' ? 'warning' : 'success'; ?>">
                      <?php echo $row['check_in_status'] == 'late' ? 'มาสาย' : 'ตรงเวลา'; ?>
                    </span>
                  </td>
                  <td><?php echo $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : '-'; ?></td>
                  <td>
                    <?php if ($row['check_out_image']): ?>
                      <img src="<?php echo BASE_URL . '/assets/uploads/attendance/' . $row['check_out_image']; ?>"
                        alt="Check-out" width="50" height="50" class="img-thumbnail" style="object-fit: cover;">
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td>
                    <button type="button" class="btn btn-sm btn-info btn-details" data-bs-toggle="modal"
                      data-bs-target="#detailsModal"
                      data-name="<?php echo htmlspecialchars($row['prefix_th'] . ' ' . $row['name_th']); ?>"
                      data-date="<?php echo htmlspecialchars($filter_date); ?>"
                      data-checkin-time="<?php echo $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : 'N/A'; ?>"
                      data-checkin-img="<?php echo BASE_URL . '/assets/uploads/attendance/' . ($row['check_in_image'] ? $row['check_in_image'] : 'default-check.png'); ?>"
                      data-checkout-time="<?php echo $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : 'N/A'; ?>"
                      data-checkout-img="<?php echo BASE_URL . '/assets/uploads/attendance/' . ($row['check_out_image'] ? $row['check_out_image'] : 'default-check.png'); ?>">
                      <i class="bi bi-eye-fill"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-warning btn-edit-shift" data-bs-toggle="modal"
                      data-bs-target="#editShiftModal" data-user-id="<?php echo $row['user_id']; ?>"
                      data-user-name="<?php echo htmlspecialchars($row['prefix_th'] . ' ' . $row['name_th']); ?>"
                      data-current-schedule-id="<?php echo $row['current_user_schedule_id']; ?>"
                      data-current-schedule-name="<?php echo htmlspecialchars($row['current_schedule_name']); ?>">
                      <i class="bi bi-clock-history"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted">ไม่พบข้อมูลการลงเวลาในวันที่เลือก</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">รายละเอียดการลงเวลา</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 id="detailsEmployeeName"></h6>
        <p id="detailsDate"></p>
        <hr>
        <div class="row">
          <div class="col-md-6 text-center">
            <h5>เวลาเข้า</h5>
            <img id="detailsCheckinImg" src="" class="img-fluid rounded mb-2" alt="Check-in">
            <p>เวลา: <strong id="detailsCheckinTime"></strong></p>
          </div>
          <div class="col-md-6 text-center">
            <h5>เวลาออก</h5>
            <img id="detailsCheckoutImg" src="" class="img-fluid rounded mb-2" alt="Check-out">
            <p>เวลา: <strong id="detailsCheckoutTime"></strong></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editShiftForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editShiftModalLabel">เปลี่ยนกะการทำงาน</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>พนักงาน: <strong id="editShiftEmployeeName"></strong></p>
          <p>กะปัจจุบัน: <strong id="editShiftCurrentSchedule"></strong></p>

          <input type="hidden" name="user_id" id="editShiftUserId">

          <div class="mb-3">
            <label for="schedule_id" class="form-label">เลือกกะใหม่:</label>
            <select class="form-control" name="schedule_id" id="schedule_id" required>
              <option value="">-- เลือกกะ --</option>
              <?php foreach ($all_schedules as $schedule): ?>
                <option value="<?php echo $schedule['id']; ?>">
                  <?php echo htmlspecialchars($schedule['name'] . ' (' . $schedule['time_in'] . ' - ' . $schedule['time_out'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div id="editShiftResult" class="mt-3"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {

    // --- 1. ควบคุม Details Modal ---
    var detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;

      // ดึงข้อมูลจาก data-* attributes
      var name = button.getAttribute('data-name');
      var date = button.getAttribute('data-date');
      var checkinTime = button.getAttribute('data-checkin-time');
      var checkinImg = button.getAttribute('data-checkin-img');
      var checkoutTime = button.getAttribute('data-checkout-time');
      var checkoutImg = button.getAttribute('data-checkout-img');

      // อัปเดต Modal
      detailsModal.querySelector('#detailsEmployeeName').textContent = 'พนักงาน: ' + name;
      detailsModal.querySelector('#detailsDate').textContent = 'วันที่: ' + date;
      detailsModal.querySelector('#detailsCheckinImg').src = checkinImg;
      detailsModal.querySelector('#detailsCheckinTime').textContent = checkinTime;
      detailsModal.querySelector('#detailsCheckoutImg').src = checkoutImg;
      detailsModal.querySelector('#detailsCheckoutTime').textContent = checkoutTime;
    });

    // --- 2. ควบคุม Edit Shift Modal ---
    var editShiftModal = document.getElementById('editShiftModal');
    var editShiftForm = document.getElementById('editShiftForm');
    var editShiftResult = document.getElementById('editShiftResult');

    editShiftModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;

      // ดึงข้อมูล
      var userId = button.getAttribute('data-user-id');
      var userName = button.getAttribute('data-user-name');
      var currentScheduleId = button.getAttribute('data-current-schedule-id');
      var currentScheduleName = button.getAttribute('data-current-schedule-name');

      // อัปเดต Modal
      editShiftModal.querySelector('#editShiftEmployeeName').textContent = userName;
      editShiftModal.querySelector('#editShiftCurrentSchedule').textContent = currentScheduleName || 'N/A';
      editShiftModal.querySelector('#editShiftUserId').value = userId;

      // ตั้งค่า dropdown ให้ตรงกับกะปัจจุบัน
      editShiftModal.querySelector('#schedule_id').value = currentScheduleId;

      // ล้างข้อความผลลัพธ์เก่า
      editShiftResult.innerHTML = '';
    });

    // --- 3. จัดการการ Submit ของ Form (AJAX) ---
    editShiftForm.addEventListener('submit', function (event) {
      event.preventDefault(); // ป้องกันการรีเฟรชหน้า

      var formData = new FormData(editShiftForm);

      fetch('ajax_update_shift.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            editShiftResult.innerHTML = '<div class="alert alert-success">เปลี่ยนกะสำเร็จ!</div>';

            // รีเฟรชหน้าเพื่อดูข้อมูลใหม่
            setTimeout(function () {
              location.reload();
            }, 1000);

          } else {
            editShiftResult.innerHTML = '<div class="alert alert-danger">' + (data.message || 'เกิดข้อผิดพลาด') + '</div>';
          }
        })
        .catch(error => {
          editShiftResult.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ</div>';
        });
    });
  });
</script>

<?php
$stmt->close();
$conn->close();
require_once '../includes/footer.php'; // เรียก Footer
?> -->