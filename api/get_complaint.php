<?php
// Track complaint by ID — public API
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Complaint ID required']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM complaints WHERE complaint_id = ?');
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'No complaint found with this ID']);
    exit;
}

echo json_encode(['success' => true, 'complaint' => $row]);
$stmt->close();
$db->close();
