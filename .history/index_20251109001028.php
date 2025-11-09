<?php
require_once 'config.php';

// ถ้า login แล้ว ให้ redirect ไปหน้า dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // ใช้ Prepared Statements เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่านที่ HASH ไว้
        if (password_verify($password, $user['password'])) {
            // รหัสผ่านถูกต้อง
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            // (อัปเดต) ใช้ชื่อจาก DB ใหม่
            $_SESSION['name'] = $user['prefix_th'] . ' ' . $user['name_th'] . ' ' . $user['lastname_th'];

            // (*** เพิ่มบรรทัดนี้ ***)
            $_SESSION['profile_image'] = $user['profile_image']; // <-- เก็บรูปโปรไฟล์ลง Session

            // Redirect ตามสิทธิ์
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: employee/dashboard.php');
            }
            exit;
        } else {
            $error_message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error_message = "ไม่พบรหัสพนักงานนี้ในระบบ";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบลงเวลางาน - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
        }
    </style>
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
                    <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>