<?php
/**
 * File: config/api-config.php
 * Konfigurasi untuk Backend API
 */

// Headers untuk API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include database
require_once(__DIR__ . '/database.php');

// Fungsi response JSON
function jsonResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Fungsi error response
function errorResponse($message, $code = 400) {
    http_response_code($code);
    jsonResponse('error', $message);
}

// Fungsi success response
function successResponse($message, $data = null) {
    jsonResponse('success', $message, $data);
}

// Validasi method
function validateMethod($allowed_methods = ['GET']) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($method, $allowed_methods)) {
        errorResponse('Method not allowed', 405);
    }
    
    return $method;
}

// Get parameter dengan validasi
function getParam($key, $default = null, $required = false) {
    $value = $_GET[$key] ?? $_POST[$key] ?? $default;
    
    if ($required && empty($value)) {
        errorResponse("Parameter '$key' is required");
    }
    
    return $value;
}
?>