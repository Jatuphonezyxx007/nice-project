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
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="navbar-nav">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2"></i>
                    <span class="d-none d-lg-inline">สวัสดี, <?php echo htmlspecialchars($current_user_name); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person-fill me-2"></i> โปรไฟล์ของฉัน
                        </a>
                    </li>
                    
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-google me-2"></i> เชื่อมต่อบัญชี Google
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-gear-fill me-2"></i> ตั้งค่า
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div></nav>

<div class="container mt-4"></div>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm py-3">
    <div class="container-fluid">
        
        <form class="d-flex me-lg-3 mb-2 mb-lg-0" role="search">
            <input class="form-control me-2" type="search" placeholder="Search..." aria-label="Search"/>
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </form>

        <div class="navbar-nav">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2"></i>
                    <span class="d-none d-lg-inline">สวัสดี, <?php echo htmlspecialchars($current_user_name); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person-fill me-2"></i> โปรไฟล์ของฉัน
                        </a>
                    </li>
                    
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-google me-2"></i> เชื่อมต่อบัญชี Google
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-gear-fill me-2"></i> ตั้งค่า
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<div class="container mt-4"></div>