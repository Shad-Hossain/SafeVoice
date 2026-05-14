<?php
require_once 'db.php';
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data        = json_decode(file_get_contents('php://input'), true);
    $complaintId = trim($data['complaint_id'] ?? '');

    if (empty($complaintId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'complaint_id required']);
        exit;
    }

    $cStmt = $db->prepare('SELECT id, user_id FROM complaints WHERE complaint_id = ?');
    $cStmt->bind_param('s', $complaintId);
    $cStmt->execute();
    $complaint = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    if (!$complaint) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        exit;
    }

    $nStmt = $db->prepare(
        'INSERT INTO pi_notifications (complaint_id, user_id, sent_at, user_response)
         VALUES (?, ?, NOW(), "pending")'
    );
    $nStmt->bind_param('si', $complaintId, $complaint['user_id']);
    $nStmt->execute();
    $nStmt->close();

    $db->query("UPDATE complaints SET status='PI Notification Sent' WHERE complaint_id='" . $db->real_escape_string($complaintId) . "'");

    echo json_encode(['success' => true, 'message' => 'PI notification sent']);
    $db->close();
    exit;
}

if ($method === 'GET') {
    $complaintId = trim($_GET['complaint_id'] ?? '');
    if (empty($complaintId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'complaint_id required']);
        exit;
    }

    $cStmt = $db->prepare("SELECT complaint_id, status FROM complaints WHERE complaint_id = ?");
    $cStmt->bind_param('s', $complaintId);
    $cStmt->execute();
    $c = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    echo json_encode([
        'success'          => true,
        'has_notification' => $c && $c['status'] === 'PI Notification Sent',
        'status'           => $c['status'] ?? '',
        'complaint_id'     => $complaintId
    ]);
    $db->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
$db->close();