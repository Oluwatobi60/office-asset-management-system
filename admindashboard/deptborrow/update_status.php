<?php
require "../../include/config.php";

// Set JSON header
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false];

// Enable error logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the received POST data
error_log("Received POST data: " . print_r($_POST, true));

// HOD approval handling
if (isset($_POST['hod_approve_id']) && isset($_POST['hod_status'])) {
    try {
        $id = intval($_POST['hod_approve_id']);
        $status = intval($_POST['hod_status']);
        
        // First check if the record exists
        $checkSql = "SELECT id FROM department_borrow_table WHERE id = :id";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Record exists, proceed with update
            $sql = "UPDATE department_borrow_table SET 
                    hod_status = :status,
                    updated_at = NOW() 
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Status updated successfully';
                error_log("Successfully updated status for ID: $id to status: $status");
            } else {
                $response['error'] = 'Failed to update status';
                error_log("Failed to update status. SQL Error: " . print_r($stmt->errorInfo(), true));
            }
        } else {
            $response['error'] = 'Record not found';
            error_log("Record not found for ID: $id");
        }
    } catch (PDOException $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
        error_log("PDO Exception: " . $e->getMessage());
    }
} else {
    $response['error'] = 'Missing required parameters';
    error_log("Missing parameters in request: " . print_r($_POST, true));
}

// Return JSON response
echo json_encode($response);
exit;
?>