<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$conn = getDB();

$sos_id = intval($_GET['sos_id'] ?? 0);

if (!$sos_id) {
    echo json_encode(["success" => false, "error" => "SOS ID required"]);
    exit;
}

// Get full SOS details with victim info
$stmt = $conn->prepare("
    SELECT
        sa.id,
        sa.user_id,
        sa.latitude,
        sa.longitude,
        sa.location_text,
        sa.crime_type,
        sa.description,
        sa.status,
        sa.created_at,
        sa.notified_count,
        u.name   AS victim_name,
        u.phone  AS victim_phone,
        u.email  AS victim_email
    FROM sos_alerts sa
    LEFT JOIN users u ON u.id = sa.user_id
    WHERE sa.id = ?
");
$stmt->bind_param("i", $sos_id);
$stmt->execute();
$sos = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sos) {
    echo json_encode(["success" => false, "error" => "SOS not found"]);
    exit;
}

// Get evidence files
$ev = $conn->prepare("SELECT file_path, file_type, uploaded_at FROM sos_evidence WHERE sos_id = ?");
$ev->bind_param("i", $sos_id);
$ev->execute();
$evidence = $ev->get_result()->fetch_all(MYSQLI_ASSOC);
$ev->close();

echo json_encode([
    "success"  => true,
    "sos"      => $sos,
    "evidence" => $evidence
]);
?>