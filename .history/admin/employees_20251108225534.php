<?php
require_once '../config.php';
require_once '../includes/header.php';

// ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: '.BASE_URL.'/employee/dashboard.php');
    exit;
}

// ตรวจสอบถ้ามีการส่งข้อความแจ้งเตือนมา
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

// --- (ใหม่) ส่วนที่ 1: ตรรกะการค้นหา ---
$search_term = $_GET['search'] ?? ''; // รับค่าค้นหา
$base_sql = "SELECT u.*, s.name as schedule_name 
             FROM users u 
             LEFT JOIN schedule_types s ON u.schedule_id = s.id";

if (!empty($search_term)) {
    // ถ้ามีการค้นหา ให้เพิ่ม WHERE clause (ใช้ Prepared Statements)
    $like_term = "%{$search_term}%";
    $sql = $base_sql . " WHERE u.id LIKE ? 
                         OR u.employee_id LIKE ? 
                         OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? 
                         OR u.email LIKE ? 
                         OR u.position LIKE ?
                         ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    // "sssss" = 5 string parameters
    $stmt->bind_param("sssss", $like_term, $like_term, $like_term, $like_term, $like_term);
    $stmt->execute();
    $employees_result = $stmt->get_result();
} else {
    // ถ้าไม่มีการค้นหา (หน้าปกติ)
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
                        <?php 
                        // รีเซ็ต pointer ของ schedules_result (ถ้าจำเป็น)
                        $schedules_result->data_seek(0); 
                        while($schedule = $schedules_result->fetch_assoc()): 
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
            <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill me-2"></i> บันทึกพนักงาน</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> รายชื่อพนักงานทั้งหมด</h5>
            <form method="GET" action="employees.php" class="d-flex" style="width: 300px;">
                <input type="text" class="form-control me-2" name="search" placeholder="ค้นหา ID, ชื่อ, Email..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
        </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>รหัสพนักงาน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>Email</th>
                        <th>ตำแหน่ง</th>
                        <th>กะเวลา</th>
                        <th>สิทธิ์</th>
                        <th>จัดการ</th> </tr>
                </thead>
                <tbody>
                    <?php if ($employees_result->num_rows > 0): ?>
                        <?php while($emp = $employees_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $emp['id']; ?></td>
                            <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                            <td>
                                <img src="<?php echo BASE_URL.'/assets/uploads/profiles/'.($emp['profile_image'] ? $emp['profile_image'] : 'default.png'); ?>" 
                                     alt="profile" width="30" height="30" class="rounded-circle me-2" style="object-fit: cover;">
                                <?php echo htmlspecialchars($emp['firstname'].' '.$emp['lastname']); ?>
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
                                <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                
                                <?php if ($emp['role'] != 'admin'): // ป้องกันการลบ Admin ?>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal" 
                                        data-id="<?php echo $emp['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($emp['firstname'].' '.$emp['lastname']); ?>"
                                        title="ลบ">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">ไม่พบข้อมูลพนักงาน <?php echo !empty($search_term) ? "ที่ตรงกับ '" . htmlspecialchars($search_term) . "'" : ""; ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div> <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                คุณแน่ใจหรือไม่ว่าต้องการลบพนักงาน:
                <br>
                <strong id="employeeNameInModal" class="text-danger"></strong>?
                <p class_mt-2 text-muted><small>การกระทำนี้ไม่สามารถย้อนกลับได้</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">ยืนยันการลบ</a>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('deleteModal');
    
    deleteModal.addEventListener('show.bs.modal', function (event) {
        // ปุ่มที่ถูกคลิก (ปุ่มลบ)
        var button = event.relatedTarget;
        
        // ดึงข้อมูลจาก data-* attributes
        var employeeId = button.getAttribute('data-id');
        var employeeName = button.getAttribute('data-name');
        
        // อัปเดตเนื้อหาใน Modal
        var modalBodyName = deleteModal.querySelector('#employeeNameInModal');
        modalBodyName.textContent = employeeName + ' (ID: ' + employeeId + ')';
        
        // อัปเดตลิงก์ในปุ่ม "ยืนยันการลบ"
        // (*** คุณต้องสร้างไฟล์ delete_employee.php ด้วย ***)
        var confirmLink = deleteModal.querySelector('#confirmDeleteLink');
        confirmLink.href = 'delete_employee.php?id=' + employeeId;
    });
});
</script>


<?php
if (isset($stmt)) {
    $stmt->close(); // ปิด statement ถ้ามีการใช้งาน
}
$conn->close();
require_once '../includes/footer.php';
?>