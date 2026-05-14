<?php
session_start();
header('Content-Type: application/json');

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user_id'] ?? 1;

$latitude = $data['latitude'];
$longitude = $data['longitude'];
$location = $data['location'];

$sql = "INSERT INTO sos_alerts
(user_id, latitude, longitude, location_text)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isss",
    $user_id,
    $latitude,
    $longitude,
    $location
);

if($stmt->execute()){

    echo json_encode([
        "success" => true,
        "sos_id" => $conn->insert_id
    ]);

}else{

    echo json_encode([
        "success" => false
    ]);
}
?>