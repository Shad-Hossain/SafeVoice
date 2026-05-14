<?php

session_start();
header('Content-Type: application/json');

include 'db.php';

$conn = getDB(); 

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "error" => "No input data"
    ]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 1;

$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;
$location = $data['location'] ?? null;

$sql = "INSERT INTO sos_alerts
(user_id, latitude, longitude, location_text)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "isss",
    $user_id,
    $latitude,
    $longitude,
    $location
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "sos_id" => $conn->insert_id
    ]);

} else {

    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}
?>