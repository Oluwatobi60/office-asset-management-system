<?php
require_once dirname(__FILE__) . "/../../include/config.php";
require_once dirname(__FILE__) . "/../../include/utils.php";

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(dirname(__FILE__))) . '/error_log.txt');
error_reporting(E_ALL);

// Set response header
header('Content-Type: application/json');

try {
    if (!isset($_GET['department-select'])) {
        throw new Exception('Department parameter is missing');
    }

    $department = trim($_GET['department-select']);
    if (empty($department)) {
        throw new Exception('Department cannot be empty');
    }    // Log the incoming request
    error_log("Fetching employees for department: " . $department);
    
    // Query the user_table for employees in the selected department with role 'user'    
    $sql = "SELECT firstname, lastname, department, username 
            FROM user_table 
            WHERE LOWER(TRIM(department)) = LOWER(:department)
            AND LOWER(role) = 'hod'
            ORDER BY firstname, lastname";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':department', $department, PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query');
    }
      // Debug raw query results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Raw query results: " . json_encode($results));
    
    $employees = [];
    
    foreach ($results as $row) {
        // Validate that required fields exist
        if (!isset($row['firstname']) || !isset($row['lastname'])) {
            error_log("Missing required fields in row: " . json_encode($row));
            continue;
        }
        
        $employees[] = [
            'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'department' => trim($row['department']),
            'username' => $row['username']
        ];
        
        // Log each employee found
        error_log("Found employee: " . json_encode($employees[count($employees)-1]));
    }

    // Log the results
    error_log("Found " . count($employees) . " employees for department: " . $department);

    if (empty($employees)) {
        error_log("No employees found for department: " . $department);
        error_log("SQL Query: " . $sql);
        error_log("Department searched: '" . $department . "'");
    } else {
        error_log("Employee data: " . json_encode($employees));
    }

    echo json_encode($employees);

} catch (Exception $e) {
    error_log("Error in get_employees.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch employees: ' . $e->getMessage(),
        'department' => $department ?? 'not set'
    ]);
}
?>
