<?php
require 'vendor/autoload.php'; // โหลด PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'your_database');
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลจากตาราง attendance
$sql = "SELECT employee_name, date, time_in, time_out, status FROM attendance";
$result = $conn->query($sql);

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// เขียนหัวตาราง
$sheet->setCellValue('A1', 'Employee Name');
$sheet->setCellValue('B1', 'Date');
$sheet->setCellValue('C1', 'Time In');
$sheet->setCellValue('D1', 'Time Out');
$sheet->setCellValue('E1', 'Status');

// เติมข้อมูล
$row = 2;
while ($data = $result->fetch_assoc()) {
  $sheet->setCellValue('A' . $row, $data['employee_name']);
  $sheet->setCellValue('B' . $row, $data['date']);
  $sheet->setCellValue('C' . $row, $data['time_in']);
  $sheet->setCellValue('D' . $row, $data['time_out']);
  $sheet->setCellValue('E' . $row, $data['status']);
  $row++;
}

// ตั้งชื่อไฟล์และ export
$fileName = 'attendance_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$fileName\"");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>