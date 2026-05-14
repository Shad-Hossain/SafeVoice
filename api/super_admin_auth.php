<?php

session_start();

header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "safevoice");

if ($conn->connect_error) {

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);

    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if(empty($username) || empty($password)) {

    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);

    exit;
}

$stmt = $conn->prepare("SELECT * FROM super_admins WHERE username = ?");

$stmt->bind_param("s", $username);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 1) {

    $admin = $result->fetch_assoc();

    if(password_verify($password, $admin['password_hash']))  {

        $_SESSION['super_admin_logged_in'] = true;
        $_SESSION['super_admin_id'] = $admin['id'];
        $_SESSION['super_admin_username'] = $admin['username'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful"
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials"
        ]);
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid credentials"
    ]);
}
?>