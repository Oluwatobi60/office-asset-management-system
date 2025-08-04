<?php
// Include database configuration file
require "../include/config.php";

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize response array with default false status
    $response = ['success' => false];
    
    // Check if HOD (Head of Department) approval ID is provided
    if (isset($_POST['hod_approve_id'])) {
        $hod_approve_id = $_POST['hod_approve_id'];
        // Update request status to approved by HOD and set approval date
        $update_sql = "UPDATE request_table SET hod_approved = 1, approval_date = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $hod_approve_id);
        $response['success'] = $stmt->execute();
    }
    
    // Check if PRO (Property/Procurement Officer) approval ID is provided
    if (isset($_POST['pro_approve_id'])) {
        $pro_approve_id = $_POST['pro_approve_id'];
        // Update request status to approved by PRO
        $update_sql = "UPDATE request_table SET pro_approved = 1 WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $pro_approve_id);
        $response['success'] = $stmt->execute();
    }
    
    // Set response header to JSON and send the response
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>