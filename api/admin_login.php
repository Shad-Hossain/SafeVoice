<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

define('ADMIN_EMAIL',         'admin@safevoice.com');
define('ADMIN_PASSWORD_HASH', '$2y$10$e0NRp/8E3PA1FvXFl7F0p.5YQ8XtGlRzKvBXuGl3TZWl.BvTnXKKu');

if ($email !== ADMIN_EMAIL) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

// ✅ সঠিকভাবে session regenerate করো — destroy করা লাগবে না
session_regenerate_id(true);

$_SESSION['admin_id']    = 1;
$_SESSION['admin_email'] = ADMIN_EMAIL;
$_SESSION['is_admin']    = true;

echo json_encode([
    'success' => true,
    'message' => 'Login successful'
]);