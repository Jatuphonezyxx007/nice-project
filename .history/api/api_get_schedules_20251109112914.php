<?php
// api/api_get_employees.php
require_once '../config.php';
header('Content-Type: application/json');

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 2. ดึงพนักงานทั้งหมด
$sql = "SELECT 
            id, 
            CONCAT(prefix_th, ' ', name_th, ' ', lastname_th) as full_name,
            profile_image
        FROM users 
        WHERE role = 'employee'
        ORDER BY name_th ASC";

$result = $conn->query($sql);
$employees = [];

while ($row = $result->fetch_assoc()) {
    // 3. แปลงเป็น Format "Resource" ของ FullCalendar
    $employees[] = [
        'id' => $row['id'], // ID ของพนักงาน (สำคัญมาก)
        'title' => $row['full_name'], // ชื่อที่จะแสดงในแถว
        'image_url' => $row['profile_image'] ? BASE_URL . '/assets/uploads/profiles/' . $row['profile_image'] : null
    ];
}

$conn->close();
echo json_encode($employees);
exit;
?>