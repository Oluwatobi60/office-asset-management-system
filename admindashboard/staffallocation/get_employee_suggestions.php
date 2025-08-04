<?php
require_once dirname(__FILE__) . "/../../include/config.php";

// Get search term from request
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

try {
    // Prepare query to search for employees with proper error handling
    $sql = "SELECT DISTINCT assigned_employee 
            FROM staff_table 
            WHERE assigned_employee LIKE :term 
            AND assigned_employee != '' 
            ORDER BY assigned_employee 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':term', "%$term%", PDO::PARAM_STR);
    $stmt->execute();
    
    $suggestions = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['assigned_employee'])) {
            $suggestions[] = array(
                'value' => $row['assigned_employee'],
                'label' => $row['assigned_employee']
            );
        }
    }
    
    // Set proper headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Return JSON response
    echo json_encode($suggestions);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Error in get_employee_suggestions.php: " . $e->getMessage());
    
    // Return empty array with error status
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => 'Database error'));
}
?>
