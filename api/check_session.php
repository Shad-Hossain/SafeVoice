<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ? AND status NOT IN ('Banned','Suspended') LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();

    if ($user) {
        echo json_encode(['loggedIn' => true, 'user' => $user]);
    } else {
        session_destroy();
        http_response_code(401);
        echo json_encode(['loggedIn' => false]);
    }
} else {
    http_response_code(401);
    echo json_encode(['loggedIn' => false]);
}
