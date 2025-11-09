<?php
require_once '../config.php';
// ตรวจสอบสิทธิ์ Admin (ไฟล์นี้จะเรียก header.php ซึ่งเช็ค login ปกติ)
// แต่เราต้องเช็คเพิ่มว่าเป็น 'admin'
require_once '../includes/header.php';

if ($current_user_role != 'admin') {
    // ถ้าไม่ใช่ admin ให้เด้งไปหน้า employee
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

// --- ดึงข้อมูลสำหรับกราฟ ---
// สรุปการเข้างานของ "วันนี้"
$today = date('Y-m-d');
$chart_data = [
    'on_time' => 0,
    'late' => 0,
    'absent' => 0
];

// 1. นับคน สาย/ตรงเวลา
$stmt_today = $conn->prepare("SELECT check_in_status, COUNT(*) as total FROM attendance WHERE attendance_date = ? GROUP BY check_in_status");
$stmt_today->bind_param("s", $today);
$stmt_today->execute();
$result_today = $stmt_today->get_result();

while ($row = $result_today->fetch_assoc()) {
    if ($row['check_in_status'] == 'on_time') {
        $chart_data['on_time'] = (int)$row['total'];
    } elseif ($row['check_in_status'] == 'late') {
        $chart_data['late'] = (int)$row['total'];
    }
}
$stmt_today->close();

// 2. นับคน "ขาดงาน" (นับจากคนทั้งหมดที่เป็น employee ลบด้วยคนที่มาแล้ว)
$total_employees_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$total_employees = (int)$total_employees_result->fetch_assoc()['total'];
$total_attended = $chart_data['on_time'] + $chart_data['late'];
$chart_data['absent'] = $total_employees - $total_attended;
if ($chart_data['absent'] < 0) $chart_data['absent'] = 0; // กันพลาด

// แปลงเป็น JSON ให้ Chart.js ใช้งาน
$json_chart_data = json_encode(array_values($chart_data));
$json_chart_labels = json_encode(array_keys($chart_data));

?>
<title>Dashboard</title>

<div class=""
<h3>Dashboard สรุปผลวันนี้ (<?php echo date('d F Y'); ?>)</h3>
<hr>

<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">ตรงเวลา (On Time)</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $chart_data['on_time']; ?> คน</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">สาย (Late)</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $chart_data['late']; ?> คน</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-header">ขาดงาน (Absent)</div>
            <div class="card-body">
                <h2 class="card-title"><?php echo $chart_data['absent']; ?> คน</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">กราฟสรุปการเข้างานวันนี้</h5>
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// ใช้ข้อมูลที่ดึงมาจาก PHP
const data = {
    labels: <?php echo $json_chart_labels; ?>,
    datasets: [{
        label: 'สรุปการเข้างาน',
        data: <?php echo $json_chart_data; ?>,
        backgroundColor: [
            'rgb(25, 135, 84)',  // Success (On Time)
            'rgb(255, 193, 7)', // Warning (Late)
            'rgb(220, 53, 69)'  // Danger (Absent)
        ],
        hoverOffset: 4
    }]
};

// ตั้งค่า Chart
const config = {
    type: 'doughnut', // ประเภทกราฟ (วงกลม)
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'สถานะการเข้างานวันนี้'
            }
        }
    }
};

// วาดกราฟ
const myChart = new Chart(
    document.getElementById('attendanceChart'),
    config
);
</script>


<?php
$conn->close();
require_once '../includes/footer.php';
?>