<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Common utility functions for the asset management system

/**
 * Logs error messages to error_log.txt
 * @param string $message The error message to log
 */
function logError($message) {
    $logFile = dirname(__FILE__) . '/../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Send a standardized JSON response
 * @param bool $success Whether the operation was successful
 * @param string $message Message to send back to the client
 * @param int $http_code HTTP status code (default: 200)
 * @param mixed $data Optional additional data to include in response
 */
function sendJsonResponse($success, $message, $http_code = 200, $data = null) {
    global $conn;
    
    // Rollback any pending transactions
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Clean output buffer
    while (ob_get_level()) ob_end_clean();
    
    // Ensure proper HTTP response code
    http_response_code($http_code);
    
    // Set JSON content type header
    header('Content-Type: application/json; charset=utf-8');
    
    // Prepare response data
    $response = [
        'success' => (bool)$success,
        'message' => (string)$message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    try {
        // Encode with strict error checking
        $json = json_encode($response, 
            JSON_THROW_ON_ERROR | 
            JSON_UNESCAPED_UNICODE | 
            JSON_UNESCAPED_SLASHES | 
            JSON_PARTIAL_OUTPUT_ON_ERROR |
            JSON_INVALID_UTF8_SUBSTITUTE
        );
        
        echo $json;
        exit;
        
    } catch (JsonException $e) {
        // Log the error
        logError("JSON encoding failed: " . $e->getMessage() . "\nData: " . print_r($response, true));
        
        // Send a safe fallback response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error occurred while processing the response'
        ]);
        exit;
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['username']);
}

/**
 * Get current logged in user
 * @return string|null
 */
function getCurrentUser() {
    return $_SESSION['username'] ?? null;
}
?>
