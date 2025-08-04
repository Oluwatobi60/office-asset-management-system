<?php
require "../include/config.php";
require_once "../../include/utils.php";
require('../../vendor/autoload.php');

try {
    // Initialize base query
    $sql = "SELECT reg_no, asset_name, description, category, dateofpurchase, quantity 
            FROM asset_table 
            WHERE 1=1";
    $params = [];

    // Add date filtering if dates are provided
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start_date = $_GET['start_date'];
        if (!strtotime($start_date)) {
            throw new Exception("Invalid start date format");
        }
        $sql .= " AND DATE(dateofpurchase) >= :start_date";
        $params[':start_date'] = $start_date;
    }

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

    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new PDOException("Failed to prepare query");
    }

    // Bind parameters and execute
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Asset Management System');
$pdf->SetTitle('Assets Report');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Title
$title = 'Assets Report';
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $title .= ' (' . $_GET['start_date'] . ' to ' . $_GET['end_date'] . ')';
}
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->Ln(10);

// Table headers
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(40, 7, 'Asset Name', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Category', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Description', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Reg No.', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Date', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Qty', 1, 1, 'C', true);

// Data rows
while($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Sanitize data before writing to PDF
    $asset_name = htmlspecialchars($data['asset_name']);
    $category = htmlspecialchars($data['category']);
    $description = htmlspecialchars($data['description']);
    $reg_no = htmlspecialchars($data['reg_no']);
    $dateofpurchase = htmlspecialchars($data['dateofpurchase']);
    $quantity = (int)$data['quantity'];

    // Write to PDF with sanitized data
    $pdf->Cell(40, 6, $asset_name, 1);
    $pdf->Cell(30, 6, $category, 1);
    $pdf->Cell(40, 6, $description, 1);
    $pdf->Cell(30, 6, $reg_no, 1);
    $pdf->Cell(30, 6, $dateofpurchase, 1);
    $pdf->Cell(20, 6, $quantity, 1, 1);
}

    // Set filename for PDF
    $filename = 'assets';
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $filename .= '_' . $_GET['start_date'] . '_to_' . $_GET['end_date'];
    }
    $filename .= '.pdf';

    // Output the PDF
    $pdf->Output($filename, 'D');

} catch (Exception $e) {
    // Log the error
    logError("PDF export error: " . $e->getMessage());
    // Redirect back with error message
    header("Location: ../assets.php?error=" . urlencode("Failed to export PDF. Please try again."));
    exit;
}