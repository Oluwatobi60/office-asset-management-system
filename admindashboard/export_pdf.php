<?php
 require "../admindashboard/include/config.php";
require '../vendor/autoload.php';

// Initialize the WHERE clause
$where_clause = "WHERE 1=1";

// Add date filtering if dates are provided
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
    $where_clause .= " AND DATE(dateofpurchase) >= '$start_date'";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
    $where_clause .= " AND DATE(dateofpurchase) <= '$end_date'";
}

$query = "SELECT reg_no, asset_name, description, category, dateofpurchase, quantity 
         FROM asset_table 
         $where_clause 
         ORDER BY reg_no ASC";

$result = $conn->query($query);

use TCPDF as TCPDF;

// Create new PDF document with landscape orientation and custom page size
$pdf = new TCPDF('L', PDF_UNIT, 'A3', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Asset Management System');
$pdf->SetTitle('Asset History Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set smaller margins to maximize content space
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(TRUE, 5);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Column headers
$headers = array('Reg No.', 'Asset Name', 'Description', 'Category', 'Quantity', 'Requested By', 'Department', 'Request Date', 'Procurement', 'HOD');
// Adjusted widths to fill A3 landscape page (420mm width - 10mm total margins = 410mm available)
$width = array(25, 35, 50, 70, 35, 25, 45, 45, 35, 30, 30); 

// Create header row
$pdf->SetFillColor(52, 73, 94);
$pdf->SetTextColor(255);
$xPos = 5;
$yPos = 5;
foreach($headers as $key => $header) {
    $pdf->SetXY($xPos, $yPos);
    $pdf->Cell($width[$key], 12, $header, 1, 0, 'C', true);
    $xPos += $width[$key];
}

// Reset text color to black for data
$pdf->SetTextColor(0);

// Fetch data from database
$query = "SELECT r.*, a.asset_name, a.description, a.category 
          FROM request_table r 
          LEFT JOIN asset_table a ON r.reg_no = a.reg_no 
          WHERE (r.hod_approved = 1 OR r.pro_approved = 1)";

// Add date filtering if dates are provided
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
    $query .= " AND DATE(r.request_date) >= '$start_date'";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
    $query .= " AND DATE(r.request_date) <= '$end_date'";
}

$query .= " ORDER BY r.request_date DESC";
$result = $conn->query($query);

// Add data rows with multicell support
$yPos = 17;
while ($data = $result->fetch_assoc()) {
    $xPos = 5;
    $maxHeight = 12; // Minimum row height
    
    // Calculate maximum height needed for this row
    $rowData = array(
        $data['reg_no'],
        $data['asset_name'],
        $data['description'],
        $data['category'],
        $data['quantity'],
        $data['requested_by'],
        $data['department'],
        $data['request_date'],
        $data['pro_approved'] == 1 ? 'Approved' : 'Not Approved',
        $data['hod_approved'] == 1 ? 'Approved' : 'Not Approved'
    );

    // First pass: calculate maximum height needed
    foreach($rowData as $key => $value) {
        // Add extra padding to height calculation
        $cellHeight = $pdf->getStringHeight($width[$key], $value) + 2;
        $maxHeight = max($maxHeight, $cellHeight);
    }

    // Second pass: print cells with calculated height
    foreach($rowData as $key => $value) {
        $pdf->SetXY($xPos, $yPos);
        // Use MultiCell for text that might need to wrap
        $pdf->MultiCell($width[$key], $maxHeight, $value, 1, 'C', false, 0);
        $xPos += $width[$key];
    }
    
    $yPos += $maxHeight;

    // Add a new page if we're near the bottom
    if ($yPos > ($pdf->getPageHeight() - 15)) {
        $pdf->AddPage();
        $yPos = 5;
        
        // Reprint headers on new page
        $xPos = 5;
        $pdf->SetFillColor(52, 73, 94);
        $pdf->SetTextColor(255);
        foreach($headers as $key => $header) {
            $pdf->SetXY($xPos, $yPos);
            $pdf->Cell($width[$key], 12, $header, 1, 0, 'C', true);
            $xPos += $width[$key];
        }
        $pdf->SetTextColor(0);
        $yPos = 17;
    }
}

// Output PDF
$pdf->Output('asset_history.pdf', 'D');