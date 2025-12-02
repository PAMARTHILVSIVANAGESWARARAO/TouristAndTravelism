<?php

// Entry point for all requests

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use TravelAI\Config\EnvConfig;
EnvConfig::getInstance();

// Set headers for CORS and JSON response
header('Content-Type: application/json; charset=utf-8');

// Handle CORS
$allowedOrigins = EnvConfig::getCorsAllowedOrigins();
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    http_response_code(204);
    exit;
}

// Handle the request using RouteConfig
use TravelAI\Routes\RouteConfig;
use TravelAI\Utils\ResponseHelper;

try {
    $router = new RouteConfig();
    $router->handleRequest();
} catch (\Exception $e) {
    // Log the error
    error_log("Unhandled exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    if (EnvConfig::isDebug()) {
        ResponseHelper::serverError($e->getMessage());
    } else {
        ResponseHelper::serverError('An unexpected error occurred');
    }
}