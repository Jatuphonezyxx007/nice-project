<?php
require_once '../config.php';
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

// ตรวจสอบถ้ามีการส่งข้อความแจ้งเตือนมา (จาก add_employee.php)
$message = '';
$message_type = 'info';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// ดึงข้อมูลกะทั้งหมด (สำหรับ Dropdown)
$schedules_result = $conn->query("SELECT * FROM schedule_types");

// ดึงข้อมูลพนักงานทั้งหมด
$employees_result = $conn->query("SELECT u.*, s.name as schedule_name 
                                  FROM users u 
                                  LEFT JOIN schedule_types s ON u.schedule_id = s.id 
                                  ORDER BY u.created_at DESC");
?>
<title>จัดการพนักงาน</title>

<div class="container">
<h3>จัดการพนักงาน</h3>
<hr>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        เพิ่มพนักงานใหม่
    </div>
    <div class="card-body">
        <form action="add_employee.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">รหัสพนักงาน <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
             <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">ตำแหน่ง</label>
                    <input type="text" name="position" class="form-control">
                </div>
                 <div class="col-md-4 mb-3">
                    <label class="form-label">กะการทำงาน (Schedule) <span class="text-danger">*</span></label>
                    <select name="schedule_id" class="form-control" required>
                        <option value="">-- เลือกกะ --</option>
                        <?php while($schedule = $schedules_result->fetch_assoc()): ?>
                            <option value="<?php echo $schedule['id']; ?>"><?php echo $schedule['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">รูปโปรไฟล์</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">เพิ่มพนักงาน</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        รายชื่อพนักงานทั้งหมด
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>รหัสพนักงาน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>Email</th>
                        <th>ตำแหน่ง</th>
                        <th>กะเวลา</th>
                        <th>สิทธิ์</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($emp = $employees_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $emp['id']; ?></td>
                        <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                        <td>
                            <img src="<?php echo BASE_URL . '/assets/uploads/profiles/' . ($emp['profile_image'] ? $emp['profile_image'] : 'default.png'); ?>" 
                                 alt="profile" width="30" height="30" class="rounded-circle me-2">
                            <?php echo htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td><?php echo htmlspecialchars($emp['position']); ?></td>
                        <td><?php echo htmlspecialchars($emp['schedule_name'] ? $emp['schedule_name'] : 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $emp['role'] == 'admin' ? 'danger' : 'secondary'; ?>">
                                <?php echo $emp['role']; ?>
                            </span>
                        </td>
                        <td>
                            </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>