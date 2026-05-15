<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
$_SESSION = [];
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, name, email, phone, password_hash, status, profile_photo, complaints_count FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    $stmt->close(); $db->close(); exit;
}

$user = $result->fetch_assoc();

if ($user['status'] === 'Banned') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your account has been banned. Contact support.']);
    $stmt->close(); $db->close(); exit;
}

if ($user['status'] === 'Suspended') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your account is suspended. Contact support.']);
    $stmt->close(); $db->close(); exit;
}

if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    $stmt->close(); $db->close(); exit;
}

// Destroy old session completely
session_unset();
session_destroy();

// Start fresh session with same secure cookie params
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['name'];

