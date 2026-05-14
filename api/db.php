<?php
// =============================================
// SafeVoice — Database Connection
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'safevoice');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// CORS — localhost XAMPP এর জন্য
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost';
if (empty($origin)) $origin = 'http://localhost';

header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
