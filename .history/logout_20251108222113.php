<?php
require_once 'config.php';

// ทำลาย Session ทั้งหมด
session_unset();
session_destroy();

// กลับไปหน้า Login
header('Location: index.php');
exit;
?>