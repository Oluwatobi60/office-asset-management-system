<?php
// Include database configuration file
require "../include/config.php";
require_once "../../include/utils.php";
// Include Composer's autoloader for PHPSpreadsheet library
require '../../vendor/autoload.php';

// Import required PHPSpreadsheet classes for Excel manipulation
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Initialize the base query
    $sql = "SELECT * FROM asset_table WHERE 1=1";
    $params = [];

    // Check if start date filter is provided and not empty
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start_date = $_GET['start_date'];
        if (!strtotime($start_date)) {
            throw new Exception("Invalid start date format");
        }
        $sql .= " AND DATE(dateofpurchase) >= :start_date";
        $params[':start_date'] = $start_date;
    }

    // Check if end date filter is provided and not empty
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $end_date = $_GET['end_date'];
        if (!strtotime($end_date)) {
            throw new Exception("Invalid end date format");
        }
        $sql .= " AND DATE(dateofpurchase) <= :end_date";
        $params[':end_date'] = $end_date;
    }

    // Add ordering
    $sql .= " ORDER BY dateofpurchase ASC";

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new PDOException("Failed to prepare query");
    }
    
    // Bind parameters and execute
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();

// Initialize a new PHPSpreadsheet instance
$spreadsheet = new Spreadsheet();
// Get the active worksheet
$sheet = $spreadsheet->getActiveSheet();

// Define column headers for the Excel file
$sheet->setCellValue('A1', 'Asset Name');
$sheet->setCellValue('B1', 'Category');
$sheet->setCellValue('C1', 'Department');
$sheet->setCellValue('D1', 'Description');
$sheet->setCellValue('E1', 'Serial Number');
$sheet->setCellValue('F1', 'Purchase Date');
$sheet->setCellValue('G1', 'Status');

// Initialize row counter for data entries
$row = 2;
// Loop through each asset record from the database
while($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Sanitize data before writing to Excel
    $asset_name = htmlspecialchars($data['asset_name']);
    $category = htmlspecialchars($data['category']);
    $description = htmlspecialchars($data['description']);
    $reg_no = htmlspecialchars($data['reg_no']);
    $dateofpurchase = htmlspecialchars($data['dateofpurchase']);
    $quantity = (int)$data['quantity'];

    // Set asset name in column A
    $sheet->setCellValue('A'.$row, $asset_name);
    // Set category in column B
    $sheet->setCellValue('B'.$row, $category);
    // Set department in column C (empty as no department in asset_table)
    $sheet->setCellValue('C'.$row, '');
    // Set description in column D
    $sheet->setCellValue('D'.$row, $description);
    // Set serial number in column E
    $sheet->setCellValue('E'.$row, $reg_no);
    // Set purchase date in column F
    $sheet->setCellValue('F'.$row, $dateofpurchase);
    // Set status in column G based on quantity
    $sheet->setCellValue('G'.$row, $quantity > 0 ? 'Available' : 'Not Available');
    // Increment row counter for next record
    $row++;
}

// Set filename for the Excel file, including date range if filters are applied
$filename = 'assets';
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $filename .= '_' . $_GET['start_date'] . '_to_' . $_GET['end_date'];
}
$filename .= '.xlsx';

// Set headers to prompt download of the Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');    // Create an Xlsx writer instance and output the Excel file to the browser
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    // Terminate script execution
    exit;
    
} catch (Exception $e) {
    // Log the error
    logError("Excel export error: " . $e->getMessage());
    // Redirect back with error message
    header("Location: ../assets.php?error=" . urlencode("Failed to export data. Please try again."));
    exit;
}