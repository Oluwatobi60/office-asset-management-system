<?php
 require "../admindashboard/include/config.php";
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Initialize the base WHERE clause for SQL query
$where_clause = "WHERE (r.hod_approved = 1 OR r.pro_approved = 1)";

// Check if start date filter is provided and not empty
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
    // Add start date condition to WHERE clause
    $where_clause .= " AND DATE(r.request_date) >= '$start_date'";
}

// Check if end date filter is provided and not empty
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
    // Add end date condition to WHERE clause
    $where_clause .= " AND DATE(r.request_date) <= '$end_date'";
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Reg No.');
$sheet->setCellValue('C1', 'Requested By');
$sheet->setCellValue('D1', 'Asset Name');
$sheet->setCellValue('E1', 'Department');
$sheet->setCellValue('F1', 'Description');
$sheet->setCellValue('G1', 'Category');
$sheet->setCellValue('H1', 'Quantity');
$sheet->setCellValue('I1', 'Request Date');
$sheet->setCellValue('J1', 'Procurement Status');
$sheet->setCellValue('K1', 'HOD Status');

// Fetch data from database
$query = "SELECT r.*, a.asset_name, a.description, a.category, r.quantity FROM request_table r 
          LEFT JOIN asset_table a ON r.reg_no = a.reg_no 
          $where_clause
          ORDER BY r.id DESC";
$result = $conn->query($query);

$row = 2; // Start from row 2 for data
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $data['id']);
    $sheet->setCellValue('B' . $row, $data['reg_no']);
    $sheet->setCellValue('C' . $row, $data['requested_by']);
    $sheet->setCellValue('D' . $row, $data['asset_name']);
    $sheet->setCellValue('E' . $row, $data['department']);
    $sheet->setCellValue('F' . $row, $data['description']);
    $sheet->setCellValue('G' . $row, $data['category']);
    $sheet->setCellValue('H' . $row, $data['quantity']);
    $sheet->setCellValue('I' . $row, $data['request_date']);
    $sheet->setCellValue('J' . $row, $data['pro_approved'] == 1 ? 'Approved' : 'Not Approved');
    $sheet->setCellValue('K' . $row, $data['hod_approved'] == 1 ? 'Approved' : 'Not Approved');
    $row++;
}

// Auto size columns
foreach(range('A','K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create Excel file
$writer = new Xlsx($spreadsheet);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="asset_history.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;