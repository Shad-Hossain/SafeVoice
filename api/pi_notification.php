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

    // Get user_id from complaint
    $cStmt = $db->prepare('SELECT user_id FROM complaints WHERE complaint_id = ?');
    $cStmt->bind_param('s', $complaintId);
    $cStmt->execute();
    $comp = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();
    $userId = $comp['user_id'] ?? null;

    // Insert or update notification
    $nStmt = $db->prepare(
        'INSERT INTO pi_notifications (complaint_id, user_id, status)
         VALUES (?,?,"sent")
         ON DUPLICATE KEY UPDATE status="sent", sent_at=NOW()'
    );
    $nStmt->bind_param('si', $complaintId, $userId);
    $nStmt->execute();
    $nStmt->close();

    // Update complaint status
    $db->query("UPDATE complaints SET status='PI Notification Sent' WHERE complaint_id='$complaintId'");

    echo json_encode(['success' => true, 'message' => 'PI notification created for user']);
    exit;
}

if ($method === 'GET') {
    $complaintId = $_GET['complaint_id'] ?? '';
    if (empty($complaintId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'complaint_id required']);
        exit;
    }
    $stmt = $db->prepare('SELECT * FROM pi_notifications WHERE complaint_id = ? ORDER BY sent_at DESC LIMIT 1');
    $stmt->bind_param('s', $complaintId);
    $stmt->execute();
    $notif = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($notif) echo json_encode(['success' => true, 'notification' => $notif]);
    else        echo json_encode(['success' => false, 'message' => 'No notification found']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
$db->close();
