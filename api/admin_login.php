<?php
// ✅ NEW FILE: api/admin_login.php
// Handles admin login and creates a proper PHP session
// This fixes the bug where evidence was not visible to admin
// because admin_login only used localStorage (client-side) and
// never set $_SESSION['admin_id'], so get_complaints_evidence.php
// always treated the admin as unauthenticated.

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

// ── Hardcoded admin credentials
// To change: update ADMIN_EMAIL and ADMIN_PASSWORD_HASH below.
// Generate a new hash with: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
define('ADMIN_EMAIL',         'admin@safevoice.com');
define('ADMIN_PASSWORD_HASH', '$2y$10$e0NRp/8E3PA1FvXFl7F0p.5YQ8XtGlRzKvBXuGl3TZWl.BvTnXKKu');
// ↑ This hash is for password "1234" — same as the old hardcoded value.
// Replace the hash above after changing your password.

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

// ✅ Create a proper server-side session for admin
// This is what was missing — without this, get_complaints_evidence.php
// could never detect the admin as logged in.
session_unset();
session_destroy();

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
session_regenerate_id(true);

$_SESSION['admin_id']    = 1;
$_SESSION['admin_email'] = ADMIN_EMAIL;
$_SESSION['is_admin']    = true;

session_write_close();

echo json_encode([
    'success' => true,
    'message' => 'Login successful'
]);