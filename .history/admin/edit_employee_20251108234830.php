<?php
require_once '../config.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    $_SESSION['message_type'] = "danger";
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$message = '';
$message_type = 'info';

// --- ส่วนที่ 1: ตรรกะการประมวลผลฟอร์ม (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // (อัปเดต) รับค่าใหม่
    $id = (int)$_POST['id'];
    $prefix = $_POST['prefix'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $name_en = $_POST['name_en'];
    $employee_id = $_POST['employee_id'];
    $email = $_POST['email'];
    $position_id = (int)$_POST['position_id'];
    $schedule_id = (int)$_POST['schedule_id'];
    $old_profile_image = $_POST['old_profile_image']; 
    $new_password = $_POST['password']; 

    // (อัปเดต) ตรรกะรูปภาพ (เหมือนเดิม)
    $profile_filename = $old_profile_image; 
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        // (โค้ดจัดการรูปภาพ... เหมือนเดิม)
        $target_dir = "../assets/uploads/profiles/";
        $profile_filename = time() . '_' . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $profile_filename;
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            if (!empty($old_profile_image) && file_exists($target_dir . $old_profile_image)) {
                unlink($target_dir . $old_profile_image);
            }
        } else {
            $profile_filename = $old_profile_image;
        }
    }

    // (อัปเดต) ตรรกะรหัสผ่าน
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET 
                    prefix = ?, firstname = ?, lastname = ?, name_en = ?, 
                    employee_id = ?, email = ?, position_id = ?, schedule_id = ?, 
                    profile_image = ?, password = ?
                WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql);
        // (อัปเดต) bind_param (11 ตัวแปร)
        $stmt_update->bind_param(
            "ssssssiissi", // s,s,s,s, s,s,i,i, s,s,i
            $prefix, $firstname, $lastname, $name_en, $employee_id, $email, 
            $position_id, $schedule_id, $profile_filename, $hashed_password, $id
        );
        
    } else {
        // (อัปเดต) ไม่เปลี่ยนรหัสผ่าน
        $sql = "UPDATE users SET 
                    prefix = ?, firstname = ?, lastname = ?, name_en = ?, 
                    employee_id = ?, email = ?, position_id = ?, schedule_id = ?, 
                    profile_image = ?
                WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql);
        // (อัปเดต) bind_param (10 ตัวแปร)
        $stmt_update->bind_param(
            "ssssssiisi", // s,s,s,s, s,s,i,i, s,i
            $prefix, $firstname, $lastname, $name_en, $employee_id, $email, 
            $position_id, $schedule_id, $profile_filename, $id
        );
    }
    
    if ($stmt_update->execute()) {
        $_SESSION['message'] = "อัปเดตข้อมูลพนักงาน ID: $id สำเร็จ";
        $_SESSION['message_type'] = "success";
        header('Location: employees.php'); 
        exit;
    } else {
        $message = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
        $message_type = "danger";
    }
    $stmt_update->close();
}
// --- จบส่วนที่ 1 ---


// --- ส่วนที่ 2: ตรรกะการดึงข้อมูลมาแสดง (GET) ---
if (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['message'] = "ไม่พบ ID พนักงาน";
    $_SESSION['message_type'] = "danger";
    header('Location: employees.php');
    exit;
}

$id_to_fetch = (int) ($_GET['id'] ?? $_POST['id']);

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

// (อัปเดต) ดึงข้อมูล "ตำแหน่ง" และ "กะ" สำหรับ Dropdowns
$positions_result = $conn->query("SELECT * FROM positions ORDER BY name_th");
$schedules_result = $conn->query("SELECT * FROM schedule_types");

// เรียก Header (หลังจาก Logic ทั้งหมด)
require_once '../includes/header.php'; 
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

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    แก้ไขข้อมูลสำหรับ: <?php echo htmlspecialchars($employee['prefix_th'].' '.$employee['firstname'] . ' ' . $employee['lastname']); ?>
                </div>
                <div class="card-body">
                    <form action="edit_employee.php" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                        <input type="hidden" name="old_profile_image" value="<?php echo htmlspecialchars($employee['profile_image']); ?>">

                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                                <select name="prefix_th" class="form-control" required>
                                    <option value="นาย" <?php echo ($employee['prefix_th'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                    <option value="นาง" <?php echo ($employee['prefix_th'] == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                    <option value="นางสาว" <?php echo ($employee['prefix_th'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                </select>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">ชื่อจริง (ไทย) <span class="text-danger">*</span></label>
                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($employee['firstname']); ?>" required>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label class="form-label">นามสกุล (ไทย) <span class="text-danger">*</span></label>
                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($employee['lastname']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                                                      <div class="col-md-4 mb-3">
                                <label class="form-label">ชื่อ-นามสกุล (อังกฤษ)</label>
                                <input type="text" name="name_en" class="form-control" value="<?php echo htmlspecialchars($employee['name_en']); ?>">
                            </div>

                        </div>

                        <div class="row">
                             <div class="col-md-4 mb-3">
                                <label class="form-label">รหัสพนักงาน <span class="text-danger">*</span></label>
                                <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <input type="password" name="password" class="form-control" placeholder="กรอกเพื่อเปลี่ยนรหัสผ่าน">
                                <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ตำแหน่งงาน <span class="text-danger">*</span></label>
                                <select name="position_id" class="form-control" required>
                                    <option value="">-- เลือกตำแหน่ง --</option>
                                    <?php while($pos = $positions_result->fetch_assoc()): ?>
                                        <option value="<?php echo $pos['id']; ?>" <?php echo ($pos['id'] == $employee['position_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos['name_th']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">กะการทำงาน <span class="text-danger">*</span></label>
                                <select name="schedule_id" class="form-control" required>
                                    <option value="">-- เลือกกะ --</option>
                                    <?php while($schedule = $schedules_result->fetch_assoc()): ?>
                                        <option value="<?php echo $schedule['id']; ?>" <?php echo ($schedule['id'] == $employee['schedule_id']) ? 'selected' : ''; ?>>
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