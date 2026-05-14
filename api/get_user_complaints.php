<?php
// ── Session check — user must be logged in ────────────────────
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.', 'redirect' => '../pages/login.html']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$db      = getDB();
$limit   = max(1, min(200, intval($_GET['limit'] ?? 50)));

// Fetch only THIS user's complaints
$stmt = $db->prepare(
    "SELECT * FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC LIMIT ?"
);
$stmt->bind_param('ii', $user_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();

// Stats — only for this user
$sr = $db->prepare(
    "SELECT status, COUNT(*) as cnt FROM complaints WHERE user_id = ? GROUP BY status"
);
$sr->bind_param('i', $user_id);
$sr->execute();
$statsResult = $sr->get_result();

$stats = [];
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = (int) $row['cnt'];
}
$sr->close();
$db->close();

echo json_encode([
    'success'    => true,
    'complaints' => $complaints,
    'total'      => count($complaints),
    'stats'      => $stats
]);
