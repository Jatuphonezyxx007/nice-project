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
<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="login-container p-4 shadow-sm bg-white rounded">
            <h3 class="text-center mb-4">ระบบลงเวลางาน</h3>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="mb-3">
                    <label for="employee_id" class="form-label">รหัสพนักงาน</label>
                    <input type="text" class="form-control" id="employee_id" name="employee_id" required 
                           <?php if ($is_blocked) echo 'disabled'; // (ใหม่) ปิดช่องถ้าถูกบล็อก ?>>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           <?php if ($is_blocked) echo 'disabled'; // (ใหม่) ปิดช่องถ้าถูกบล็อก ?>>
                </div>
                <button type="submit" class="btn btn-primary w-100" 
                        <?php if ($is_blocked) echo 'disabled'; // (ใหม่) ปิดปุ่มถ้าถูกบล็อก ?>>
                    เข้าสู่ระบบ
                </button>
            </form>
        </div>
    </div>
    </body>
</html>