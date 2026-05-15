<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$conn = getDB();
$data = json_decode(file_get_contents("php://input"), true);

$sos_id  = intval($data['sos_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;
$action  = $data['action'] ?? 'respond'; // 'respond' or 'ignore'

if (!$user_id || !$sos_id) {
    echo json_encode(["success" => false, "error" => "Missing data"]);
    exit;
}

$status = ($action === 'respond') ? 'responded' : 'ignored';

$stmt = $conn->prepare(
    "UPDATE sos_notifications SET status = ? WHERE sos_id = ? AND notified_user_id = ?"
);
$stmt->bind_param("sii", $status, $sos_id, $user_id);
$stmt->execute();
$stmt->close();

// If responding, log responder info
if ($action === 'respond') {
    $ins = $conn->prepare(
        "INSERT IGNORE INTO sos_responders (sos_id, responder_id, responded_at)
         VALUES (?, ?, NOW())"
    );
    $ins->bind_param("ii", $sos_id, $user_id);
    $ins->execute();
    $ins->close();
}

echo json_encode(["success" => true, "action" => $action]);
?>