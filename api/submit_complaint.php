<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.', 'redirect' => '../pages/login.html']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

$type        = trim($data['type']          ?? '');
$description = trim($data['description']   ?? '');
$date        = trim($data['incident_date'] ?? '');
$location    = trim($data['location']      ?? '');
$is_anon     = isset($data['is_anonymous']) && $data['is_anonymous'] ? 1 : 0;

if (empty($type) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type and description are required']);
    exit;
}

$complaint_id  = 'SV-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
$incident_date = null;
if (!empty($date)) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $date);
    if ($dt) $incident_date = $dt->format('Y-m-d H:i:s');
}

$db = getDB();

$stmt = $db->prepare(
    'INSERT INTO complaints (complaint_id, user_id, type, incident_date, location, description, is_anonymous, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, "Submitted")'
);
$stmt->bind_param('ssisssi', $complaint_id, $user_id, $type, $incident_date, $location, $description, $is_anon);

if ($stmt->execute()) {
    // Update user's complaints count
    $upd = $db->prepare("UPDATE users SET complaints_count = complaints_count + 1 WHERE id = ?");
    $upd->bind_param('i', $user_id);
    $upd->execute();
    $upd->close();

    echo json_encode([
        'success'      => true,
        'complaint_id' => $complaint_id,
        'message'      => 'Complaint submitted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save complaint: ' . $stmt->error]);
}

$stmt->close();
$db->close();
