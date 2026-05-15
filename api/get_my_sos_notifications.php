<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$conn = getDB();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

// Get notifications for this user
$stmt = $conn->prepare("
    SELECT
        sn.id         AS notif_id,
        sn.sos_id,
        sn.status     AS notif_status,
        sn.created_at AS notif_time,
        sa.latitude,
        sa.longitude,
        sa.location_text,
        sa.crime_type,
        sa.status     AS sos_status,
        sa.created_at AS sos_time,
        sa.notified_count,
        u.name        AS victim_name
    FROM sos_notifications sn
    JOIN sos_alerts sa ON sa.id = sn.sos_id
    LEFT JOIN users u  ON u.id = sa.user_id
    WHERE sn.notified_user_id = ?
      AND sa.status = 'active'
    ORDER BY sn.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    "success"       => true,
    "notifications" => $notifications,
    "count"         => count($notifications)
]);
?>