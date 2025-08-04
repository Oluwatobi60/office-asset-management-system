<?php
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
?>
