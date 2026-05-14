<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
session_unset();
session_destroy();
require_once 'db.php';
echo json_encode(['success' => true, 'message' => 'Logged out.']);