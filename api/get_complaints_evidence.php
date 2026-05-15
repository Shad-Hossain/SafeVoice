<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$complaint_id = trim($_GET['complaint_id'] ?? '');
if (empty($complaint_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'complaint_id required']);
    exit;
}

// Must be logged in — either user (owns the complaint) or admin
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)
         || (isset($_SESSION['admin_id']))
         || (isset($_COOKIE['sv_admin']) && $_COOKIE['sv_admin'] === '1');

$is_user  = isset($_SESSION['user_id']);

if (!$is_admin && !$is_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db = getDB();

// If regular user, verify they own the complaint
if (!$is_admin && $is_user) {
    $chk = $db->prepare("SELECT id FROM complaints WHERE complaint_id = ? AND user_id = ? LIMIT 1");
    $chk->bind_param('si', $complaint_id, $_SESSION['user_id']);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        $chk->close(); $db->close(); exit;
    }
    $chk->close();
}

$stmt = $db->prepare("SELECT id, file_path, file_name, uploaded_at FROM complaint_evidence WHERE complaint_id = ? ORDER BY uploaded_at ASC");
$stmt->bind_param('s', $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'files' => $files]);