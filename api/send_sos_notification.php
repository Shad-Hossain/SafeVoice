<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$conn = getDB();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "No input data"]);
    exit;
}

$sos_id     = intval($data['sos_id'] ?? 0);
$user_id    = $_SESSION['user_id'] ?? 1;
$latitude   = $data['latitude']  ?? null;
$longitude  = $data['longitude'] ?? null;
$location   = $data['location']  ?? '';

if (!$sos_id) {
    echo json_encode(["success" => false, "error" => "Invalid SOS ID"]);
    exit;
}

// Get victim info
$stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$victim = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get nearby users (all active users except the victim — in real system: filter by distance)
$stmt2 = $conn->prepare(
    "SELECT id, name, email FROM users WHERE id != ? AND status = 'Active' LIMIT 20"
);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$nearbyUsers = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Insert notifications for each nearby user
$notified_count = 0;
foreach ($nearbyUsers as $u) {
    $ins = $conn->prepare(
        "INSERT INTO sos_notifications (sos_id, notified_user_id, status) VALUES (?, ?, 'sent')"
    );
    $ins->bind_param("ii", $sos_id, $u['id']);
    $ins->execute();
    $ins->close();
    $notified_count++;
}

// Update sos_alerts with notification_sent status
$upd = $conn->prepare(
    "UPDATE sos_alerts SET notification_sent = 1, notified_count = ? WHERE id = ?"
);
$upd->bind_param("ii", $notified_count, $sos_id);
$upd->execute();
$upd->close();

echo json_encode([
    "success"         => true,
    "notified_count"  => $notified_count,
    "victim_name"     => $victim['name'] ?? 'Unknown',
    "sos_id"          => $sos_id,
    "location"        => $location
]);
?>