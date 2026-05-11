<?php
// Get all complaints — for user dashboard (most recent first)
require_once 'db.php';

$db     = getDB();
$limit  = intval($_GET['limit'] ?? 50);
$result = $db->query("SELECT * FROM complaints ORDER BY submitted_at DESC LIMIT $limit");

$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}

// Stats
$stats = [];
$sr = $db->query('SELECT status, COUNT(*) as cnt FROM complaints GROUP BY status');
while ($row = $sr->fetch_assoc()) {
    $stats[$row['status']] = (int)$row['cnt'];
}

echo json_encode([
    'success'    => true,
    'complaints' => $complaints,
    'total'      => count($complaints),
    'stats'      => $stats
]);
$db->close();
