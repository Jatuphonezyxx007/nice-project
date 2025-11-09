<?php
require_once '../config.php';
require_once '../includes/header.php'; // เรียก Header

// 1. ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

// (*** อัปเดต: ตรรกะการกรอง ***)
// เปลี่ยนเป็น Start Date และ End Date (สามารถเว้นว่างได้)
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

// 3. ดึงข้อมูลกะทั้งหมด (สำหรับ Modal แก้ไข)
$schedules_result = $conn->query("SELECT * FROM schedule_types");
$all_schedules = [];
while ($row = $schedules_result->fetch_assoc()) {
    $all_schedules[] = $row;
}

// 4. ดึง Admin ทั้งหมด (สำหรับ Dropdown ผู้อนุมัติ)
$admins_result = $conn->query("SELECT id, prefix_th, name_th, lastname_th FROM users WHERE role = 'admin' ORDER BY name_th");
$all_admins = [];
while ($row = $admins_result->fetch_assoc()) {
    $all_admins[] = $row;
}

// 5. (*** อัปเดต: SQL Query ***)
// สร้าง SQL แบบ Dynamic WHERE
$sql = "SELECT a.id as attendance_id, a.attendance_date, a.check_in_time, a.check_in_image, 
               a.check_in_status, a.check_out_time, a.check_out_image, a.approver_id,
               u.id as user_id, u.prefix_th, u.name_th, u.lastname_th, u.employee_id,
               u.schedule_id as current_user_schedule_id, 
               s.name as current_schedule_name,
               s.time_in, s.time_out
/* ... (ส่วนต้นของ SQL) ... */
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        
        -- (*** โค้ดใหม่ ***)
        -- 1. หากะที่ถูกกำหนดไว้ ณ วันที่ลงเวลา (a.attendance_date)
        LEFT JOIN employee_schedules es ON u.id = es.user_id 
            AND a.attendance_date BETWEEN es.start_date AND es.end_date
            LEFT JOIN schedule_types s ON es.schedule_id = s.id";

$sql_conditions = [];
$params = [];
$param_types = "";

// เพิ่มเงื่อนไข Start Date
if (!empty($filter_start_date)) {
    $sql_conditions[] = "a.attendance_date >= ?";
    $params[] = $filter_start_date;
    $param_types .= "s";
}

// เพิ่มเงื่อนไข End Date
if (!empty($filter_end_date)) {
    $sql_conditions[] = "a.attendance_date <= ?";
    $params[] = $filter_end_date;
    $param_types .= "s";
}

// รวมเงื่อนไข
if (!empty($sql_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $sql_conditions);
}

// (*** อัปเดต: การเรียงลำดับ ***)
// ต้องเรียงตามวันที่ก่อน
$sql .= " ORDER BY a.attendance_date DESC, a.check_in_time DESC";

$stmt = $conn->prepare($sql);

// Bind parameters (ถ้ามี)
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

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
        <div class="col-md-3">
          <label for="filter_start_date" class="form-label">วันที่เริ่มต้น:</label>
          <input type="date" class="form-control" id="filter_start_date" name="filter_start_date"
            value="<?php echo htmlspecialchars($filter_start_date); ?>">
        </div>
        <div class="col-md-3">
          <label for="filter_end_date" class="form-label">วันที่สิ้นสุด:</label>
          <input type="date" class="form-control" id="filter_end_date" name="filter_end_date"
            value="<?php echo htmlspecialchars($filter_end_date); ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> กรอง</button>
          <a href="attendance.php" class="btn btn-outline-secondary">ล้างตัวกรอง</a>
        </div>
      </form>
    </div>
  </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                ข้อมูลการลงเวลา
                <?php
                // สร้างข้อความสรุปการกรอง
                if (!empty($filter_start_date) && !empty($filter_end_date)) {
                    echo "(จาก " . date('d/m/Y', strtotime($filter_start_date)) . " ถึง " . date('d/m/Y', strtotime($filter_end_date)) . ")";
                } elseif (!empty($filter_start_date)) {
                    echo "(ตั้งแต่ " . date('d/m/Y', strtotime($filter_start_date)) . ")";
                } elseif (!empty($filter_end_date)) {
                    echo "(ถึง " . date('d/m/Y', strtotime($filter_end_date)) . ")";
                } else {
                    echo "(ทั้งหมด)";
                }
                ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>พนักงาน</th>
                            <th>วันที่</th>
                            <th>เวลาเข้า</th>
                            <th>ภาพ (เข้า)</th>
                            <th>สถานะ</th>
                            <th>เวลาออก</th>
                            <th>ภาพ (ออก)</th>
                            <th>OT (ชม.)</th>
                            <th>ผู้อนุมัติ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_result->num_rows > 0): ?>
                            <?php while ($row = $attendance_result->fetch_assoc()): ?>
                                
                                <?php
                                // (ตรรกะเดิม: ไม่ต้องแก้ไข)
                                // 1. คำนวณสถานะ
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

                                // 2. คำนวณ OT
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
                                    <td>
                                        <?php echo htmlspecialchars($row['prefix_th'] . ' ' . $row['name_th']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['employee_id']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($row['attendance_date'])); ?></strong>
                                    </td>
                                    <td><?php echo $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : '-'; ?></td>
                                    <td>
                                        <img src="<?php echo BASE_URL . '/assets/uploads/attendance/' . ($row['check_in_image'] ? $row['check_in_image'] : 'default-check.png'); ?>"
                                             alt="Check-in" width="50" height="50" class="img-thumbnail" style="object-fit: cover;">
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_bg; ?>">
                                            <?php echo $status_text; ?>
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
                                        <strong class="text-danger"><?php echo $ot_hours > 0 ? $ot_hours . ' ชม.' : '-'; ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($ot_hours > 0): ?>
                                            <select class="form-select form-select-sm approver-select" 
                                                    data-attendance-id="<?php echo $row['attendance_id']; ?>"
                                                    style="min-width: 150px;">
                                                <option value="">-- เลือกผู้อนุมัติ --</option>
                                                <?php foreach ($all_admins as $admin): ?>
                                                    <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $row['approver_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($admin['prefix_th'] . ' ' . $admin['name_th']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-success approver-status" id="status-<?php echo $row['attendance_id']; ?>"></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info btn-details" data-bs-toggle="modal"
                                        data-bs-target="#detailsModal"
                                        data-name="<?php echo htmlspecialchars($row['prefix_th'] . ' ' . $row['name_th']); ?>"
                                        data-date="<?php echo htmlspecialchars($row['attendance_date']); ?>" data-checkin-time="<?php echo $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : 'N/A'; ?>"
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
                                <td colspan="10" class="text-center text-muted">ไม่พบข้อมูลการลงเวลาในช่วงวันที่ที่เลือก</td>
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
document.addEventListener('DOMContentLoaded', function() {
    
    // (*** อัปเดต: Details Modal JS ***)
    var detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;

      var name = button.getAttribute('data-name');
      var date = button.getAttribute('data-date'); // ดึงวันที่จากแถว
      var checkinTime = button.getAttribute('data-checkin-time');
      var checkinImg = button.getAttribute('data-checkin-img');
      var checkoutTime = button.getAttribute('data-checkout-time');
      var checkoutImg = button.getAttribute('data-checkout-img');

      // ฟังก์ชัน Format วันที่ (ถ้าต้องการ)
      var formattedDate = date;
      try {
        // พยายามแปลงวันที่เป็น d/m/Y (ถ้า data-date เป็น Y-m-d)
        var dateObj = new Date(date + 'T00:00:00'); // 'T00:00:00' เพื่อป้องกัน Timezone
        formattedDate = dateObj.toLocaleDateString('th-TH', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
      } catch(e) { /* ใช้วันที่เดิมถ้าแปลงไม่ได้ */ }

      detailsModal.querySelector('#detailsEmployeeName').textContent = 'พนักงาน: ' + name;
      detailsModal.querySelector('#detailsDate').textContent = 'วันที่: ' + formattedDate; // ใช้วันที่ใหม่
      detailsModal.querySelector('#detailsCheckinImg').src = checkinImg;
      detailsModal.querySelector('#detailsCheckinTime').textContent = checkinTime;
      detailsModal.querySelector('#detailsCheckoutImg').src = checkoutImg;
      detailsModal.querySelector('#detailsCheckoutTime').textContent = checkoutTime;
    });

    // (*** ไม่มีการแก้ไข ส่วนที่เหลือ ***)

    // --- 2. ควบคุม Edit Shift Modal ---
    var editShiftModal = document.getElementById('editShiftModal');
    var editShiftForm = document.getElementById('editShiftForm');
    var editShiftResult = document.getElementById('editShiftResult');

    editShiftModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var userId = button.getAttribute('data-user-id');
      var userName = button.getAttribute('data-user-name');
      var currentScheduleId = button.getAttribute('data-current-schedule-id');
      var currentScheduleName = button.getAttribute('data-current-schedule-name');

      editShiftModal.querySelector('#editShiftEmployeeName').textContent = userName;
      editShiftModal.querySelector('#editShiftCurrentSchedule').textContent = currentScheduleName || 'N/A';
      editShiftModal.querySelector('#editShiftUserId').value = userId;
      editShiftModal.querySelector('#schedule_id').value = currentScheduleId;
      editShiftResult.innerHTML = '';
    });

    // --- 3. จัดการการ Submit ของ Form (AJAX) ---
    editShiftForm.addEventListener('submit', function (event) {
      event.preventDefault(); 
      var formData = new FormData(editShiftForm);

      fetch('ajax_update_shift.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            editShiftResult.innerHTML = '<div class="alert alert-success">เปลี่ยนกะสำเร็จ!</div>';
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

    // --- 4. ควบคุม Dropdown ผู้อนุมัติ ---
    document.querySelectorAll('.approver-select').forEach(function(select) {
        select.addEventListener('change', function() {
            var attendanceId = this.getAttribute('data-attendance-id');
            var approverId = this.value;
            var statusElement = document.getElementById('status-' + attendanceId);

            statusElement.textContent = 'กำลังบันทึก...';
            statusElement.className = 'text-muted';
            
            var formData = new FormData();
            formData.append('attendance_id', attendanceId);
            formData.append('approver_id', approverId);

            fetch('ajax_update_approver.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.textContent = 'บันทึกแล้ว!';
                    statusElement.className = 'text-success';
                } else {
                    statusElement.textContent = 'ล้มเหลว!';
                    statusElement.className = 'text-danger';
                }
                setTimeout(() => { statusElement.textContent = ''; }, 2000);
            })
            .catch(error => {
                statusElement.textContent = 'Error!';
                statusElement.className = 'text-danger';
                setTimeout(() => { statusElement.textContent = ''; }, 2000);
            });
        });
    });
});
</script>

<?php
$stmt->close();
$conn->close();
require_once '../includes/footer.php'; // เรียก Footer
?>