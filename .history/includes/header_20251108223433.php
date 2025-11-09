<?php
// ไฟล์นี้จะถูก require โดยหน้าอื่นๆ ที่ต้องการการ login
// เราเริ่ม session ใน config.php แล้ว
if (!isset($_SESSION['user_id'])) {
    // ถ้ายังไม่ login ให้เด้งกลับไปหน้า index
    // เราใช้ BASE_URL ที่กำหนดใน config.php
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ดึงสิทธิ์ (role) และ ชื่อ (name) จาก session
$current_user_role = $_SESSION['role'];
$current_user_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f4f7f6; }
        /* ทำให้ปุ่ม logout ดูดีขึ้นเมื่อมีไอคอน */
        .btn-logout {
            display: inline-flex;
            align-items: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="bi bi-calendar-check-fill me-2"></i> Attendance System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            
            <ul class="navbar-nav me-auto">
                <?php if ($current_user_role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/employees.php">
                            <i class="bi bi-people-fill me-1"></i> จัดการพนักงาน
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/dashboard.php">
                            <i class="bi bi-clock-history me-1"></i> ลงเวลา
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <form class="d-flex me-lg-3" role="search">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"/>
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>

            <div class="d-flex align-items-center">
                <span class="navbar-text text-white me-3">
                    <i class="bi bi-person-circle me-2"></i> สวัสดี, <?php echo htmlspecialchars($current_user_name); ?>
                </span>
                <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline-danger btn-logout">
                    <i class="bi bi-box-arrow-right me-1"></i> ออกจากระบบ
                </a>
            </div>

        </div>
    </div>
</nav>

<div class="container mt-4">