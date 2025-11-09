<?php
require_once 'config.php';

// ถ้า login แล้ว ให้ redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit;
}

$error_message = '';
$is_blocked = false;
// (ใหม่) ดึง IP ของผู้ใช้
$ip_address = $_SERVER['REMOTE_ADDR'];

// (ใหม่) 1. ตรวจสอบว่า IP นี้ถูกบล็อกหรือไม่ (ป้องกัน Brute Force)
$stmt_check = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND timestamp > (NOW() - INTERVAL ? SECOND)");
$time_period = LOGIN_TIME_PERIOD;
$stmt_check->bind_param("si", $ip_address, $time_period);
$stmt_check->execute();
$stmt_check->bind_result($attempts_count);
$stmt_check->fetch();
$stmt_check->close();

if ($attempts_count >= MAX_LOGIN_ATTEMPTS) {
    $is_blocked = true;
    $error_message = "คุณพยายาม Login ผิดพลาดบ่อยเกินไป กรุณารอสักครู่ (15 นาที) แล้วลองใหม่";
}

// 2. ถ้าไม่ถูกบล็อก และมีการส่งฟอร์มมา
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_blocked) {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // (ป้องกัน SQL Injection - เหมือนเดิม)
    $stmt = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // (เหมือนเดิม) ตรวจสอบรหัสผ่านที่ HASH ไว้
        if (password_verify($password, $user['password'])) {
            
            // --- (ใหม่) สำเร็จ: ล้างประวัติการพยายามผิด และสร้าง Session ใหม่ ---
            
            // 1. ล้างการพยายาม
            $stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_clear->bind_param("s", $ip_address);
            $stmt_clear->execute();
            $stmt_clear->close();

            // 2. (ป้องกัน Session Fixation) สร้าง ID Session ใหม่ทั้งหมด
            session_regenerate_id(true);

            // 3. ตั้งค่า Session (เหมือนเดิม + อัปเดตใหม่)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['prefix_th'] . ' ' . $user['name_th'] . ' ' . $user['lastname_th'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            // 4. (ใหม่) ป้องกัน Session Hijacking (เก็บ User Agent ไว้เช็ค)
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Redirect
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: employee/dashboard.php');
            }
            exit;

        } else {
            // (ใหม่) รหัสผ่านผิด: บันทึกการพยายาม
            $stmt_log = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
            $stmt_log->bind_param("s", $ip_address);
            $stmt_log->execute();
            $stmt_log->close();
            
            $error_message = "รหัสพนักงาน หรือ รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        // (ใหม่) ไม่พบชื่อผู้ใช้: บันทึกการพยายาม (เพื่อป้องกันการสุ่มชื่อ)
        $stmt_log = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
        $stmt_log->bind_param("s", $ip_address);
        $stmt_log->execute();
        $stmt_log->close();
        
        $error_message = "รหัสพนักงาน หรือ รหัสผ่านไม่ถูกต้อง";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    </head>
<body class="d-flex vh-100 align-items-center justify-content-center">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                
                <div class="card shadow-lg border-0 rounded-4">
                    
                    <div class="card-body p-4 p-sm-5">
                        
                        <div class="text-center mb-4">
                            <i class="bi bi-clock-history display-4 text-primary"></i>
                            <h2 class="h4 fw-bold mt-3 mb-0">ระบบลงเวลางาน</h2>
                        </div>
                        
                        <?php if (isset($error_message) && $error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="index.php">

                            <div class="input-group mb-3">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-person-badge"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="text" class="form-control border-start-0" id="employee_id" name="employee_id" placeholder="รหัสพนักงาน" required 
                                           <?php if (isset($is_blocked) && $is_blocked) echo 'disabled'; ?>>
                                    <label for="employee_id">รหัสพนักงาน</label>
                                </div>
                            </div>

                            <div class="input-group mb-4">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-shield-lock"></i>
                                </span>
                                <div class="form-floating">
                                    <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="รหัสผ่าน" required 
                                           <?php if (isset($is_blocked) && $is_blocked) echo 'disabled'; ?>>
                                    <label for="password">รหัสผ่าน</label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" 
                                    <?php if (isset($is_blocked) && $is_blocked) echo 'disabled'; ?>>
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                เข้าสู่ระบบ
                            </button>
                            
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>