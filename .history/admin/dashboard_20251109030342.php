<?php
require_once '../config.php';
require_once '../includes/header.php';

if ($current_user_role != 'admin') {
    header('Location: ' . BASE_URL . '/employee/dashboard.php');
    exit;
}

// --- (ส่วนที่ 1: สรุปวันนี้ - เหมือนเดิม) ---
$today = date('Y-m-d');
$chart_data_today = [
    'on_time' => 0,
    'late' => 0,
    'absent' => 0
];
// ... (โค้ดนับ on_time, late - เหมือนเดิม) ...
$stmt_today = $conn->prepare("SELECT check_in_status, COUNT(*) as total FROM attendance WHERE attendance_date = ? GROUP BY check_in_status");
$stmt_today->bind_param("s", $today);
$stmt_today->execute();
$result_today = $stmt_today->get_result();
while ($row = $result_today->fetch_assoc()) {
    if ($row['check_in_status'] == 'on_time') {
        $chart_data_today['on_time'] = (int) $row['total'];
    } elseif ($row['check_in_status'] == 'late') {
        $chart_data_today['late'] = (int) $row['total'];
    }
}
$stmt_today->close();
// ... (โค้ดนับ absent - เหมือนเดิม) ...
$total_employees_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$total_employees = (int) $total_employees_result->fetch_assoc()['total'];
$total_attended = $chart_data_today['on_time'] + $chart_data_today['late'];
$chart_data_today['absent'] = $total_employees - $total_attended;
if ($chart_data_today['absent'] < 0)
    $chart_data_today['absent'] = 0;

$json_chart_data_today = json_encode(array_values($chart_data_today));
$json_chart_labels_today = json_encode(array_keys($chart_data_today));

// 1. (ใหม่) รับค่าเดือนที่เลือก (ถ้าไม่เลือก ให้เป็นเดือนปัจจุบัน)
// ค่าที่ได้จะเป็น "YYYY-MM" เช่น "2025-11"
$selected_month = $_GET['month'] ?? date('Y-m');

// 2. (ใหม่) หาวันแรกและวันสุดท้ายของเดือนที่เลือก
$start_date_month = date('Y-m-01', strtotime($selected_month));
$end_date_month = date('Y-m-t', strtotime($selected_month));
$days_in_month = date('t', strtotime($selected_month));


// --- (*** ใหม่: ส่วนที่ 2: สรุป 30 วันย้อนหลัง (สำหรับกราฟเส้น) ***) ---
$trend_data = [];
$labels_trend = [];
$data_on_time = [];
$data_late = [];
$data_absent = [];

// 3. (ใหม่) สร้าง key วันที่สำหรับ "ทุกวัน" ในเดือนที่เลือก
for ($i = 1; $i <= $days_in_month; $i++) {
    $day_num = str_pad($i, 2, '0', STR_PAD_LEFT); // "01", "02", ...
    $date_key = $selected_month . '-' . $day_num; // "2025-11-01"

    $labels_trend[] = $day_num; // ป้ายกำกับแกน X ( "01", "02", ...)
    $trend_data[$date_key] = [
        'on_time' => 0,
        'late' => 0
    ];
}

// 4. (ใหม่) ดึงข้อมูล "ทั้งเดือน"
$stmt_trend = $conn->prepare(
    "SELECT attendance_date, check_in_status, COUNT(*) as total 
     FROM attendance 
     WHERE attendance_date BETWEEN ? AND ?
     GROUP BY attendance_date, check_in_status"
);
// (ใหม่) ใช้ start/end ของเดือน
$stmt_trend->bind_param("ss", $start_date_month, $end_date_month);
$stmt_trend->execute();
$result_trend = $stmt_trend->get_result();

// 5. ใส่ข้อมูลลงใน array (เหมือนเดิม)
while ($row = $result_trend->fetch_assoc()) {
    $date_key = $row['attendance_date'];
    if (isset($trend_data[$date_key])) {
        if ($row['check_in_status'] == 'on_time') {
            $trend_data[$date_key]['on_time'] = (int) $row['total'];
        } elseif ($row['check_in_status'] == 'late') {
            $trend_data[$date_key]['late'] = (int) $row['total'];
        }
    }
}
$stmt_trend->close();

// 6. สร้าง array ข้อมูลสำหรับ Chart.js (เหมือนเดิม)
foreach ($trend_data as $date_key => $data) {
    $attended = $data['on_time'] + $data['late'];
    $absent_count = $total_employees - $attended;

    $data_on_time[] = $data['on_time'];
    $data_late[] = $data['late'];
    $data_absent[] = ($absent_count < 0) ? 0 : $absent_count;
}

$json_labels_trend = json_encode($labels_trend);
$json_data_on_time = json_encode($data_on_time);
$json_data_late = json_encode($data_late);
$json_data_absent = json_encode($data_absent);

?>
<title>Dashboard</title>

<div class="container">
    <h3>Dashboard สรุปผลวันนี้ (<?php echo date('d F Y'); ?>)</h3>
    <hr>

    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">ตรงเวลา (On Time)</div>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $chart_data_today['on_time']; ?> คน</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">สาย (Late)</div>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $chart_data_today['late']; ?> คน</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">ขาดงาน (Absent)</div>
                <div class="card-body">
                    <h2 class="card-title"><?php echo $chart_data_today['absent']; ?> คน</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">กราฟสรุปการเข้างานวันนี้</h5>
                    <canvas id="attendanceChartToday"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-file-earmark-excel-fill me-2"></i> Export รายงาน</h5>
                </div>
                <div class="card-body">
                    <p>เลือกช่วงวันที่ที่ต้องการ export เป็นไฟล์ Excel (CSV)</p>
                    <form action="export_attendance.php" method="POST" target="_blank">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">วันที่เริ่มต้น:</label>
                                <input type="date" class="form-control" name="start_date" id="start_date"
                                    value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">วันที่สิ้นสุด:</label>
                                <input type="date" class="form-control" name="end_date" id="end_date"
                                    value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-download me-2"></i> Export Excel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">สรุปการเข้างานย้อนหลัง 30 วัน</h5>
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div> -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            สรุปการเข้างานรายเดือน (<?php echo date('F Y', strtotime($selected_month)); ?>)
                        </h5>

                        <form method="GET" action="dashboard.php" class="d-flex" style="width: 250px;">
                            <input type="month" class="form-control me-2" name="month"
                                value="<?php echo htmlspecialchars($selected_month); ?>">
                            <button type="submit" class="btn btn-primary btn-sm">ดู</button>
                        </form>
                    </div>
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    // (*** กราฟที่ 1: กราฟวงกลม - เหมือนเดิม ***)
    // (อัปเดต) เปลี่ยนชื่อ id เป็น 'attendanceChartToday'
    const dataToday = {
        labels: <?php echo $json_chart_labels_today; ?>,
        datasets: [{
            label: 'สรุปการเข้างาน',
            data: <?php echo $json_chart_data_today; ?>,
            backgroundColor: [
                'rgb(25, 135, 84)',  // Success (On Time)
                'rgb(255, 193, 7)', // Warning (Late)
                'rgb(220, 53, 69)'  // Danger (Absent)
            ],
            hoverOffset: 4
        }]
    };
    const configToday = {
        type: 'doughnut',
        data: dataToday,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'สถานะการเข้างานวันนี้' }
            }
        }
    };
    new Chart(
        document.getElementById('attendanceChartToday'),
        configToday
    );


    // (*** ใหม่: กราฟที่ 2: กราฟเส้น ***)
    const labelsTrend = <?php echo $json_labels_trend; ?>;
    const dataTrend = {
        labels: labelsTrend,
        datasets: [
            {
                label: 'ตรงเวลา',
                data: <?php echo $json_data_on_time; ?>,
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.1
            },
            {
                label: 'มาสาย',
                data: <?php echo $json_data_late; ?>,
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: true,
                tension: 0.1
            },
            {
                label: 'ขาดงาน',
                data: <?php echo $json_data_absent; ?>,
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.1
            }
        ]
    };
    const configTrend = {
        type: 'line',
        data: dataTrend,
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'แนวโน้มการเข้างาน 30 วัน' }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    };
    new Chart(
        document.getElementById('dailyTrendChart'),
        configTrend
    );
</script>

<?php
$conn->close();
require_once '../includes/footer.php';
?>