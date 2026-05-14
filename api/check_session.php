<?php
session_start();
header('Content-Type: application/json');

if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user' => [
            'id'    => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name'  => $_SESSION['user_name'],
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['loggedIn' => false]);
}
