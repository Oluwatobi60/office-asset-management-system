<?php
// Include database configuration file
require "../include/config.php";

// Function to log errors
function logError($message, $error = null) {
    $errorLog = date('Y-m-d H:i:s') . " - " . $message;
    if ($error) {
        $errorLog .= " - " . $error->getMessage();
    }
    error_log($errorLog . "\n", 3, "../logs/error.log");
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize response array
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Start transaction
        $conn->beginTransaction();

        // Check if HOD approval ID is provided
        if (isset($_POST['hod_approve_id'])) {
            $hod_approve_id = $_POST['hod_approve_id'];
            
            // Update request status to approved by HOD and set approval date
            $update_sql = "UPDATE request_table SET hod_approved = 1, approval_date = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $conn->prepare($update_sql);
            $stmt->bindParam(':id', $hod_approve_id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update HOD approval status");
            }
            
            $response['success'] = true;
            $response['message'] = 'HOD approval updated successfully';
        }
        
        // Check if PRO approval ID is provided
        if (isset($_POST['pro_approve_id'])) {
            $pro_approve_id = $_POST['pro_approve_id'];
            
            // Check if HOD has approved first
            $check_sql = "SELECT hod_approved FROM request_table WHERE id = :id";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':id', $pro_approve_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $hod_status = $check_stmt->fetchColumn();
            
            if (!$hod_status) {
                throw new Exception("HOD approval is required before Procurement can approve");
            }
            
            // Update request status to approved by PRO
            $update_sql = "UPDATE request_table SET pro_approved = 1 WHERE id = :id";
            $stmt = $conn->prepare($update_sql);
            $stmt->bindParam(':id', $pro_approve_id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update Procurement approval status");
            }
            
            $response['success'] = true;
            $response['message'] = 'Procurement approval updated successfully';
        }
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        logError("Status update failed", $e);
    }
    
    // Set response header to JSON and send the response
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>