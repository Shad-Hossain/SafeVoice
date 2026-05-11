<?php
// =============================================
// SafeVoice — Submit Complaint API
// POST /api/submit_complaint.php
// =============================================

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$type        = trim($data['type']        ?? '');
$description = trim($data['description'] ?? '');
$date        = trim($data['incident_date'] ?? '');
$location    = trim($data['location']    ?? '');
$is_anon     = isset($data['is_anonymous']) && $data['is_anonymous'] ? 1 : 0;

if (empty($type) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type and description are required']);
    exit;
}

// Generate unique complaint ID: SV-2026-XXXX
$complaint_id = 'SV-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

// Parse incident date
$incident_date = null;
if (!empty($date)) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $date);
    if ($dt) $incident_date = $dt->format('Y-m-d H:i:s');
}

$db = getDB();

// Insert complaint
$stmt = $db->prepare(
    'INSERT INTO complaints (complaint_id, type, incident_date, location, description, is_anonymous, status)
     VALUES (?, ?, ?, ?, ?, ?, "Submitted")'
);
$stmt->bind_param('sssssi', $complaint_id, $type, $incident_date, $location, $description, $is_anon);

if ($stmt->execute()) {
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
