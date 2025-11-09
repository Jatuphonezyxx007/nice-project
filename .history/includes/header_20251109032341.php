<?php
// ไฟล์นี้จะถูก require โดยหน้าอื่นๆ ที่ต้องการการ login
// เราเริ่ม session ใน config.php แล้ว
if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] != $_SERVER['HTTP_USER_AGENT']) {
    // ถ้า User Agent (บราวเซอร์) ไม่ตรงกัน ให้ทำลาย Session ทิ้งทันที
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php?error=hijacked');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // ถ้ายังไม่ login ให้เด้งกลับไปหน้า index
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// ดึงสิทธิ์ (role) และ ชื่อ (name) จาก session
$current_user_role = $_SESSION['role'];
$current_user_name = $_SESSION['name'];
$current_user_profile_image = $_SESSION['profile_image'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai+Looped:wght@300;400;500;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* (ใหม่) 2. ตั้งค่า Font หลักของเว็บให้เป็น Noto Sans Thai Looped */
        body {
            background-color: #f8f9fa;
            font-family: 'Noto Sans Thai Looped', sans-serif;
            /* <-- เพิ่มบรรทัดนี้ */
        }

        .dropdown-menu-end {
            min-width: 220px;
        }

        /* (ใหม่) สไตล์สำหรับรูปโปรไฟล์ใน Navbar */
        .navbar-profile-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 8px;
            border: 2px solid #fff3;
            /* เพิ่มขอบให้ดูมีมิติ */
        }
    </style>
</head>

<!-- <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm py-3">
        <div class="container-fluid">

            <a class="navbar-brand" href="dashboard.php">Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <ul class="navbar-nav me-auto">
                <?php if ($current_user_role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/employees.php">จัดการพนักงาน</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/attendance.php">บันทึกเวลาทั้งหมด</a>
                    </li>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/dashboard.php">ลงเวลา</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/attendance.php">การลงเวลา</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($current_user_profile_image): ?>
                            <img src="<?php echo BASE_URL . '/assets/uploads/profiles/' . $current_user_profile_image; ?>"
                                alt="Profile" class="navbar-profile-img">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-4 me-2"></i>
                        <?php endif; ?>
                        <span class="d-none d-lg-inline">สวัสดี,
                            <?php echo htmlspecialchars($current_user_name); ?></span>
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
                        <li>
                            <hr class="dropdown-divider">
                        </li>
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

    <div class="container mt-4"></div> -->

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm py-3">
        <div class="container-fluid">

            <a class="navbar-brand" href="dashboard.php">Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($current_user_role == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/employees.php">จัดการพนักงาน</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/attendance.php">บันทึกเวลาทั้งหมด</a>
                        </li>

                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/dashboard.php">ลงเวลา</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/employee/attendance.php">การลงเวลา</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">

                            <?php if ($current_user_profile_image): ?>
                                <img src="<?php echo BASE_URL . '/assets/uploads/profiles/' . $current_user_profile_image; ?>"
                                    alt="Profile" class="navbar-profile-img me-2">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-4 me-2"></i>
                            <?php endif; ?>

                            <span class="d-none d-lg-inline">สวัสดี,
                                <?php echo htmlspecialchars($current_user_name); ?></span>
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
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <div class="container mt-4">
    </div>
</body>