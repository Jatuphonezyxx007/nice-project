<?php
require_once '../config.php';
require_once '../includes/header.php'; // เรียก Header

// 1. ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: '.BASE_URL);
    exit;
}

// 2. ตรรกะการเลือกสัปดาห์
$selected_date = $_GET['week_date'] ?? date('Y-m-d');

// คำนวณหาวันจันทร์ (Start) และ วันอาทิตย์ (End) ของสัปดาห์ที่เลือก
$day_of_week = date('N', strtotime($selected_date)); // 1 (จันทร์) - 7 (อาทิตย์)
$week_start_date = date('Y-m-d', strtotime($selected_date . ' -' . ($day_of_week - 1) . ' days'));
$week_end_date = date('Y-m-d', strtotime($week_start_date . ' +6 days'));

$success_message = '';

// 3. (*** สำคัญ ***) ตรรกะการบันทึกข้อมูล (เมื่อ Admin กด Submit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $posted_start_date = $_POST['start_date'];
    $posted_end_date = $_POST['end_date'];
    $assignments = $_POST['assignments'] ?? [];
    $admin_id = $current_user_id; // ID ของ Admin ที่กำลังล็อกอิน

    // ใช้ Transaction เพื่อความปลอดภัย
    $conn->begin_transaction();
    try {
        // เตรียมคำสั่ง SQL (ใช้ ON DUPLICATE KEY UPDATE)
        // ถ้ามีข้อมูล user/week นี้อยู่แล้ว ให้อัปเดต, ถ้าไม่มี ให้เพิ่มใหม่
        $sql = "INSERT INTO employee_schedules (user_id, schedule_id, start_date, end_date, assigned_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    schedule_id = VALUES(schedule_id), 
                    assigned_by = VALUES(assigned_by), 
                    updated_at = CURRENT_TIMESTAMP";
        $stmt = $conn->prepare($sql);

        foreach ($assignments as $user_id => $schedule_id) {
            // เราจะบันทึกเฉพาะคนที่มีการเลือกกะ (ไม่เอา '0')
            if ($schedule_id > 0) {
                $stmt->bind_param("iissi", $user_id, $schedule_id, $posted_start_date, $posted_end_date, $admin_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $success_message = '<div class="alert alert-success">บันทึกตารางกะสำเร็จ!</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $success_message = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
    }
    
    // อัปเดต $week_start_date และ $week_end_date ให้ตรงกับที่ POST มา
    $week_start_date = $posted_start_date;
    $week_end_date = $posted_end_date;
}

// 4. ดึงข้อมูลที่จำเป็น
// (กะทั้งหมด)
$schedules_result = $conn->query("SELECT * FROM schedule_types ORDER BY id");
$all_schedules = $schedules_result->fetch_all(MYSQLI_ASSOC);

// (พนักงานทั้งหมด - ไม่รวม Admin)
$users_result = $conn->query("SELECT id, prefix_th, name_th, lastname_th FROM users WHERE role != 'admin' ORDER BY name_th");
$all_employees = $users_result->fetch_all(MYSQLI_ASSOC);

// (ดึงกะที่บันทึกไว้แล้วของสัปดาห์นี้)
$sql_current = "SELECT user_id, schedule_id FROM employee_schedules WHERE start_date = ?";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param("s", $week_start_date);
$stmt_current->execute();
$current_schedules_result = $stmt_current->get_result();
$current_assignments = [];
while ($row = $current_schedules_result->fetch_assoc()) {
    $current_assignments[$row['user_id']] = $row['schedule_id'];
}
?>

<title>จัดการตารางกะ (รายสัปดาห์)</title>

<div class="container">
    <h3><i class="bi bi-calendar-week me-2"></i> จัดการตารางกะ (รายสัปดาห์)</h3>
    <hr>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="manage_shifts.php" class="row g-3 align-items.center">
                <div class="col-md-4">
                    <label for="week_date" class="form-label">เลือกสัปดาห์ (เลือกวันใดก็ได้ในสัปดาห์)</label>
                    <input type="date" class="form-control" id="week_date" name="week_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> เลือก</button>
                    <a href="manage_shifts.php" class="btn btn-outline-secondary">สัปดาห์ปัจจุบัน</a>
                </div>
            </form>
        </div>
    </div>

    <?php echo $success_message; // แสดงผลลัพธ์การบันทึก ?>

    <form method="POST" action="manage_shifts.php">
        <input type="hidden" name="start_date" value="<?php echo $week_start_date; ?>">
        <input type="hidden" name="end_date" value="<?php echo $week_end_date; ?>">

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    ตารางกะสำหรับสัปดาห์: 
                    <span class="text-primary">
                        <?php echo date('d M Y', strtotime($week_start_date)); ?>
                        - 
                        <?php echo date('d M Y', strtotime($week_end_date)); ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>พนักงาน</th>
                                <th>กะที่ได้รับมอบหมาย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_employees as $emp): ?>
                                <?php
                                // หากมีกะที่เคยบันทึกไว้ ให้เลือกกะนั้น
                                $assigned_schedule_id = $current_assignments[$emp['id']] ?? '0';
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($emp['prefix_th'] . ' ' . $emp['name_th'] . ' ' . $emp['lastname_th']); ?>
                                    </td>
                                    <td>
                                        <select class="form-select" name="assignments[<?php echo $emp['id']; ?>]">
                                            <option value="0">-- ไม่กำหนดกะ (หยุด/ลา) --</option>
                                            <?php foreach ($all_schedules as $schedule): ?>
                                                <option value="<?php echo $schedule['id']; ?>" <?php echo ($schedule['id'] == $assigned_schedule_id) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($schedule['name'] . ' (' . $schedule['time_in'] . ' - ' . $schedule['time_out'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-save me-2"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once '../includes/footer.php'; // เรียก Footer
?>