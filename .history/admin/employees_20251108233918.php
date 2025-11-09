<?php
require_once '../config.php';
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

// ตรวจสอบข้อความแจ้งเตือน
$message = '';
$message_type = 'info';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// (ใหม่) ดึงข้อมูล "ตำแหน่งงาน" ทั้งหมดสำหรับ Dropdown
$positions_result = $conn->query("SELECT * FROM positions ORDER BY name_th");

// (เดิม) ดึงข้อมูล "กะ" ทั้งหมดสำหรับ Dropdown
$schedules_result = $conn->query("SELECT * FROM schedule_types");

// --- (อัปเดต) ส่วนที่ 1: ตรรกะการค้นหา ---
$search_term = $_GET['search'] ?? '';
$base_sql = "SELECT u.*, 
                    s.name as schedule_name,
                    p.name_th as position_name_th  /* (ใหม่) ดึงชื่อตำแหน่ง */
             FROM users u 
             LEFT JOIN schedule_types s ON u.schedule_id = s.id
             LEFT JOIN positions p ON u.position_id = p.id /* (ใหม่) JOIN ตารางตำแหน่ง */";

if (!empty($search_term)) {
    $like_term = "%{$search_term}%";
    // (อัปเดต) เพิ่มการค้นหา name_en และ position_name_th
    $sql = $base_sql . " WHERE u.employee_id LIKE ? 
                         OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? 
                         OR u.name_en LIKE ?                 /* (ใหม่) */
                         OR u.email LIKE ? 
                         OR p.name_th LIKE ?                 /* (ใหม่) */
                         ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like_term, $like_term, $like_term, $like_term, $like_term); // (อัปเดต) "sssss"
    $stmt->execute();
    $employees_result = $stmt->get_result();
} else {
    $sql = $base_sql . " ORDER BY u.created_at DESC";
    $employees_result = $conn->query($sql);
}
// --- จบส่วนที่ 1 ---

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
            <i class="bi bi-person-plus-fill me-2"></i> เพิ่มพนักงานใหม่
        </div>
        <div class="card-body">
            <form action="add_employee.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                        <select name="prefix" class="form-control" required>
                            <option value="">-- เลือก --</option>
                            <option value="นาย">นาย</option>
                            <option value="นาง">นาง</option>
                            <option value="นางสาว">นางสาว</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">ชื่อจริง (ไทย) <span class="text-danger">*</span></label>
                        <input type="text" name="firstname" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">นามสกุล (ไทย) <span class="text-danger">*</span></label>
                        <input type="text" name="lastname" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ชื่อ-นามสกุล (อังกฤษ)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="John Doe">
                    </div>

                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">รหัสพนักงาน <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ตำแหน่งงาน <span class="text-danger">*</span></label>
                        <select name="position_id" class="form-control" required>
                            <option value="">-- เลือกตำแหน่ง --</option>
                            <?php
                            $positions_result->data_seek(0); // Reset pointer
                            while ($pos = $positions_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $pos['id']; ?>"><?php echo $pos['name_th']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">กะการทำงาน <span class="text-danger">*</span></label>
                        <select name="schedule_id" class="form-control" required>
                            <option value="">-- เลือกกะ --</option>
                            <?php
                            $schedules_result->data_seek(0);
                            while ($schedule = $schedules_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $schedule['id']; ?>"><?php echo $schedule['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">รูปโปรไฟล์</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill me-2"></i>
                    บันทึกพนักงาน</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> รายชื่อพนักงานทั้งหมด</h5>
                <form method="GET" action="employees.php" class="d-flex" style="width: 300px;">
                    <input type="text" class="form-control me-2" name="search" placeholder="ค้นหา รหัส, ชื่อ, Email..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสพนักงาน</th>
                            <th>ชื่อ-นามสกุล (ไทย)</th>
                            <th>ชื่อ-นามสกุล (อังกฤษ)</th>
                            <th>Email</th>
                            <th>ตำแหน่ง</th>
                            <th>กะเวลา</th>
                            <th>สิทธิ์</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees_result->num_rows > 0): ?>
                            <?php while ($emp = $employees_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                    <td>
                                        <img src="<?php echo BASE_URL . '/assets/uploads/profiles/' . ($emp['profile_image'] ? $emp['profile_image'] : 'default.png'); ?>"
                                            alt="profile" width="30" height="30" class="rounded-circle me-2"
                                            style="object-fit: cover;">
                                        <?php echo htmlspecialchars($emp['prefix'] . ' ' . $emp['firstname'] . ' ' . $emp['lastname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['name_en']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position_name_th'] ? $emp['position_name_th'] : 'N/A'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($emp['schedule_name'] ? $emp['schedule_name'] : 'N/A'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['role'] == 'admin' ? 'danger' : 'secondary'; ?>">
                                            <?php echo $emp['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning"
                                            title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <?php if ($emp['role'] != 'admin'): ?>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal" data-id="<?php echo $emp['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($emp['prefix'] . ' ' . $emp['firstname'] . ' ' . $emp['lastname']); ?>"
                                                title="ลบ">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">ไม่พบข้อมูลพนักงาน
                                    <?php echo !empty($search_term) ? "ที่ตรงกับ '" . htmlspecialchars($search_term) . "'" : ""; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteModal" ...>
    ...
</div>
<script>
    ...
</script>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
require_once '../includes/footer.php';
?>