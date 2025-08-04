<?php
// submit_repair.php

// Prevent any direct script output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(dirname(dirname(__FILE__))) . '/error_log.txt');


// Load dependencies early to catch any potential errors
require_once dirname(__FILE__) . '/../../include/config.php';
require_once dirname(__FILE__) . '/../../include/utils.php';

// Clean any existing output buffers
while (ob_get_level()) ob_end_clean();
ob_start();

// Set proper headers early
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Register error handler to convert all PHP errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {    // Log the error first
    logError("PHP Error: [$errno] $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

// Log Error function is imported from utils.php - do not redefine here

// Register exception handler for uncaught exceptions
set_exception_handler(function($exception) {
    $errorContext = [
        'message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ];
    
    // Log the error with full context
    logError("Uncaught Exception", $errorContext);
    
    // Send error response
    sendJsonResponse(
        false,
        'An unexpected error occurred. Please try again later.',
        500
    );
});

// Dependencies already loaded at the top of the file, including utils.php
// sendJsonResponse function is imported from utils.php, do not redefine here

// Function to verify database structure
function ensureDatabaseStructure($conn) {
    try {
        // Check if status column exists in staff_table
        $columns = $conn->query("SHOW COLUMNS FROM staff_table")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $columns)) {
            $conn->exec("ALTER TABLE staff_table ADD COLUMN status VARCHAR(50) DEFAULT NULL");
            logError("Added status column to staff_table");
        }
        
        // Simply verify that repair_asset table is accessible
        try {
            $conn->query("SELECT id, asset_id, status FROM repair_asset LIMIT 1");
            return true;
        } catch (PDOException $e) {
            logError("Error accessing repair_asset table", [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        logError("Database structure setup failed", [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Invalid request method', 405);
    }

    // Check authentication
    if (!isset($_SESSION['username'])) {
        sendJsonResponse(false, 'User not authenticated', 401);
    }
    
    // Log request information
    logError("Processing repair request", [
        'username' => $_SESSION['username'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set'
    ]);

    // Get and validate input data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendJsonResponse(false, 'No input data received', 400);
    }

    // Parse JSON input
    try {
        $data = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        sendJsonResponse(false, 'Invalid JSON data: ' . $e->getMessage(), 400);
    }

    // Validate required fields
    if (empty($data['asset_id']) || !is_numeric($data['asset_id'])) {
        sendJsonResponse(false, 'Missing or invalid asset_id', 400);
    }

    if (empty($data['asset_info']) || !is_array($data['asset_info'])) {
        sendJsonResponse(false, 'Missing or invalid asset_info', 400);
    }

    // Extract and validate asset info
    $asset_id = (int)$data['asset_id'];
    $asset_info = $data['asset_info'];

    // Validate required asset info fields
    $required_fields = ['reg_no', 'asset_name', 'department'];
    foreach ($required_fields as $field) {
        if (empty($asset_info[$field])) {
            sendJsonResponse(false, "Missing required field: {$field}", 400);
        }
    }

    // Ensure database structure
    if (!ensureDatabaseStructure($conn)) {
        sendJsonResponse(false, 'Database structure is not valid', 500);
    }    // Start transaction
    if (!$conn->beginTransaction()) {
        logError("Failed to start transaction");
        throw new Exception("Failed to start database transaction");
    }
      logError("Started database transaction");
    try {
        // Check if asset exists and its current status
        logError("Checking asset status", [
            'asset_id' => $asset_id,
            'input' => $data
        ]);
        
        $check_stmt = $conn->prepare("SELECT id, status FROM staff_table WHERE id = ?");
        if (!$check_stmt) {
            logError("Failed to prepare asset check query", [
                'error' => implode(', ', $conn->errorInfo())
            ]);
            throw new Exception("Database error while checking asset status");
        }

        if (!$check_stmt->execute([$asset_id])) {
            logError("Failed to execute asset check query", [
                'error' => implode(', ', $check_stmt->errorInfo()),
                'params' => ['asset_id' => $asset_id]
            ]);
            throw new Exception("Failed to check asset status");
        }
        
        $asset_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$asset_data) {
            logError("Asset not found", [
                'asset_id' => $asset_id,
                'sql' => $check_stmt->queryString
            ]);
            throw new Exception("Asset not found");
        }

        logError("Asset status check", [
            'asset_id' => $asset_id,
            'status' => $asset_data['status'] ?? 'not set',
            'data' => $asset_data
        ]);        if (isset($asset_data['status']) && $asset_data['status'] === 'Under Repair') {
            throw new Exception("Asset is already marked for repair");
        }

        // Update staff_table status
        $update_stmt = $conn->prepare("UPDATE staff_table SET status = 'Under Repair' WHERE id = ?");
        if (!$update_stmt->execute([$asset_id])) {
            logError("Failed to update asset status", [
                'error' => implode(', ', $update_stmt->errorInfo()),
                'params' => ['asset_id' => $asset_id]
            ]);
            throw new Exception("Failed to update asset status");
        }

        // Get user's full name
        $user_stmt = $conn->prepare("SELECT CONCAT(firstname, ' ', lastname) as full_name FROM user_table WHERE username = ?");
        if (!$user_stmt->execute([$_SESSION['username']])) {
            logError("Failed to fetch user details", [
                'error' => implode(', ', $user_stmt->errorInfo()),
                'username' => $_SESSION['username']
            ]);
            throw new Exception("Failed to get user details");
        }
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $reporter_name = $user_data['full_name'] ?? $_SESSION['username']; // Fallback to username if full name not found

        // Insert repair record
        $insert_sql = "INSERT INTO repair_asset (
            asset_id, reg_no, asset_name, department, reported_by,
            description, category, quantity, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Under Repair')";

        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            logError("Failed to prepare repair record insert", [
                'error' => implode(', ', $conn->errorInfo())
            ]);
            throw new Exception("Database error while creating repair record");
        }        $insert_params = [
            $asset_id,
            $asset_info['reg_no'],
            $asset_info['asset_name'],
            $asset_info['department'],
            $reporter_name, // Using full name from user_table
            $asset_info['description'] ?? 'Marked for repair',
            $asset_info['category'] ?? 'General',
            (int)($asset_info['quantity'] ?? 1)
        ];

        if (!$insert_stmt->execute($insert_params)) {
            logError("Failed to insert repair record", [
                'error' => implode(', ', $insert_stmt->errorInfo()),
                'params' => $insert_params
            ]);
            throw new Exception("Failed to create repair record");
        }

        // Commit transaction
        if (!$conn->commit()) {
            logError("Failed to commit transaction", [
                'error' => implode(', ', $conn->errorInfo())
            ]);
            throw new Exception("Failed to save repair record");
        }

        // Send success response
        sendJsonResponse(true, 'Asset has been marked for repair', 200, [
            'asset_id' => $asset_id,
            'status' => 'Under Repair'
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }

} catch (PDOException $e) {
    logError("Database error", [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);

    $error_message = 'Database error occurred';
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $error_message = 'This asset is already marked for repair';
    }

    sendJsonResponse(false, $error_message, 500);
} catch (Exception $e) {
    logError("Application error", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    sendJsonResponse(false, $e->getMessage(), 500);
}