<?php
require "../../include/config.php";

// Set JSON header
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false];

// Procurement approval handling
if (isset($_POST['pro_approve_id']) && isset($_POST['pro_status'])) {
    try {
        $id = $_POST['pro_approve_id'];
        $status = $_POST['pro_status'];
        
        $sql = "UPDATE borrow_table SET pro_status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Status updated successfully';
        } else {
            $response['error'] = 'Failed to update status';
        }
    } catch (PDOException $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Missing required parameters';
}

// Return JSON response
echo json_encode($response);
exit;
?>