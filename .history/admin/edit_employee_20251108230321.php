<?php
require_once '../config.php';
require_once '../includes/header.php'; // เรียก Header (รวม Font และ CSS)

// ตรวจสอบสิทธิ์ Admin
if ($current_user_role != 'admin') {
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

$message = '';
$message_type = 'info';

// --- (ส่วนที่ 1) ตรรกะการประมวลผลฟอร์ม (เมื่อมีการ POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่าทั้งหมดจากฟอร์ม
    $id = (int)$_POST['id'];
    $employee_id = $_POST['employee_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $position = $_POST['position'];
    $schedule_id = (int)$_POST['schedule_id'];
    $old_profile_image = $_POST['old_profile_image']; // รูปเดิม
    $new_password = $_POST['password']; // รหัสผ่านใหม่ (ถ้ามี)

    // 1. ตรรกะการอัปเดตรูปภาพ
    $profile_filename = $old_profile_image; // ใช้รูปเดิมเป็นค่าเริ่มต้น
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../assets/uploads/profiles/";
        // สร้างชื่อไฟล์ใหม่
        $profile_filename = time() . '_' . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $profile_filename;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // อัปโหลดสำเร็จ, ลบรูปเก่า (ถ้ามี)
            if (!empty($old_profile_image) && file_exists($target_dir . $old_profile_image)) {
                unlink($target_dir . $old_profile_image);
            }
        } else {
            // อัปโหลดล้มเหลว, ใช้รูปเดิม
            $profile_filename = $old_profile_image;
        }
    }

    // 2. ตรรกะการอัปเดตรหัสผ่าน (อัปเดตเฉพาะเมื่อกรอก)
    if (!empty($new_password)) {
        // มีการกรอกรหัสผ่านใหม่
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
$sql = "UPDATE users SET 
                    employee_id = ?, firstname = ?, lastname = ?, email = ?, 
                    position = ?, schedule_id = ?, profile_image = ?, password = ?
                WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql);
        
        // vvv แก้ไขบรรทัดนี้ vvv
        $stmt_update->bind_param(
            "sssssisi", // <-- แก้ไขให้ถูกต้อง (9 ตัว)
            $employee_id, $firstname, $lastname, $email, $position,
            $schedule_id, $profile_filename, $hashed_password, $id
        );
        
    } else {
        // ไม่มีการกรอกรหัสผ่านใหม่ (ไม่ต้องอัปเดต)
        $sql = "UPDATE users SET 
                    employee_id = ?, firstname = ?, lastname = ?, email = ?, 
                    position = ?, schedule_id = ?, profile_image = ?
                WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql);
        $stmt_update->bind_param(
            "sssssisi",
            $employee_id, $firstname, $lastname, $email, $position,
            $schedule_id, $profile_filename, $id
        );
    }
    
    // 3. สั่ง Execute
    if ($stmt_update->execute()) {
        $_SESSION['message'] = "อัปเดตข้อมูลพนักงาน ID: $id สำเร็จ";
        $_SESSION['message_type'] = "success";
        header('Location: employees.php'); // กลับไปหน้าหลัก
        exit;
    } else {
        // (กรณี Error)
        $message = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
        $message_type = "danger";
    }
    $stmt_update->close();
}
// --- จบส่วนที่ 1 ---


// --- (ส่วนที่ 2) ตรรกะการดึงข้อมูลมาแสดงในฟอร์ม (เมื่อมีการ GET) ---
if (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['message'] = "ไม่พบ ID พนักงาน";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}

// ถ้าไม่ใช่ POST (คือ GET) ให้ดึง ID จาก GET
// ถ้าเป็น POST ที่ Error (ด้านบน) ให้ดึง ID จาก POST เพื่อแสดงฟอร์มเดิม
$id_to_fetch = (int)($_GET['id'] ?? $_POST['id']);

// ดึงข้อมูลพนักงาน
$stmt_select = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_select->bind_param("i", $id_to_fetch);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows == 0) {
    $_SESSION['message'] = "ไม่พบข้อมูลพนักงาน ID: $id_to_fetch";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}
$employee = $result->fetch_assoc();
$stmt_select->close();

// ดึงข้อมูลกะทั้งหมด (สำหรับ Dropdown)
$schedules_result = $conn->query("SELECT * FROM schedule_types");

?>
<title>แก้ไขข้อมูลพนักงาน</title>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-pencil-square me-2"></i> แก้ไขข้อมูลพนักงาน</h3>
                <a href="employees.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> กลับไปหน้าพนักงาน
                </a>
            </div>
            <hr>

            <?php if ($message): // แสดง Error ถ้าเกิดจากการ POST ล้มเหลว ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    แก้ไขข้อมูลสำหรับ: <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                </div>
                <div class="card-body">
                    <form action="edit_employee.php" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                        <input type="hidden" name="old_profile_image" value="<?php echo htmlspecialchars($employee['profile_image']); ?>">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">รหัสพนักงาน <span class="text-danger">*</span></label>
                                <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($employee['firstname']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($employee['lastname']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <input type="password" name="password" class="form-control" placeholder="กรอกเพื่อเปลี่ยนรหัสผ่าน">
                                <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ตำแหน่ง</label>
                                <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($employee['position']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">กะการทำงาน (Schedule) <span class="text-danger">*</span></label>
                                <select name="schedule_id" class="form-control" required>
                                    <option value="">-- เลือกกะ --</option>
                                    <?php while($schedule = $schedules_result->fetch_assoc()): ?>
                                        <?php 
                                        // ตรวจสอบว่ากะไหนคืออันที่พนักงานคนนี้ใช้อยู่
                                        $selected = ($schedule['id'] == $employee['schedule_id']) ? 'selected' : ''; 
                                        ?>
                                        <option value="<?php echo $schedule['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($schedule['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">รูปโปรไฟล์ใหม่</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                                <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรูป</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รูปโปรไฟล์ปัจจุบัน:</label><br>
                            <img src="<?php echo BASE_URL . '/assets/uploads/profiles/' . ($employee['profile_image'] ? $employee['profile_image'] : 'default.png'); ?>" 
                                 alt="Profile Image" width="100" height="100" class="img-thumbnail" style="object-fit: cover;">
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill me-2"></i> บันทึกการเปลี่ยนแปลง</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer.php';
?>