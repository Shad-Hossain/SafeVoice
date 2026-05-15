<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$complaint_id = trim($_POST['complaint_id'] ?? '');
if (empty($complaint_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Complaint ID is required.']);
    exit;
}

// Verify this complaint belongs to the logged-in user
$db = getDB();
$check = $db->prepare("SELECT id FROM complaints WHERE complaint_id = ? AND user_id = ? LIMIT 1");
$check->bind_param('si', $complaint_id, $_SESSION['user_id']);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Complaint not found or access denied.']);
    $check->close(); $db->close(); exit;
}
$check->close();

if (empty($_FILES['evidence']) || !is_array($_FILES['evidence']['name'])) {
    echo json_encode(['success' => true, 'message' => 'No files uploaded.', 'files' => []]);
    $db->close(); exit;
}

$uploadDir = __DIR__ . '/../uploads/evidence/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
$max_size = 10 * 1024 * 1024; // 10MB
$uploaded = [];
$errors   = [];

$file_count = count($_FILES['evidence']['name']);
for ($i = 0; $i < $file_count; $i++) {
    if ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error for: ' . htmlspecialchars($_FILES['evidence']['name'][$i]);
        continue;
    }

    $mime = mime_content_type($_FILES['evidence']['tmp_name'][$i]);
    if (!in_array($mime, $allowed_types)) {
        $errors[] = 'Invalid file type: ' . htmlspecialchars($_FILES['evidence']['name'][$i]);
        continue;
    }

    if ($_FILES['evidence']['size'][$i] > $max_size) {
        $errors[] = 'File too large (max 10MB): ' . htmlspecialchars($_FILES['evidence']['name'][$i]);
        continue;
    }

    $ext       = pathinfo($_FILES['evidence']['name'][$i], PATHINFO_EXTENSION);
    $safe_name = preg_replace('/[^a-zA-Z0-9]/', '', $complaint_id) . '_' . time() . '_' . $i . '.' . strtolower($ext);
    $dest      = $uploadDir . $safe_name;

    if (move_uploaded_file($_FILES['evidence']['tmp_name'][$i], $dest)) {
        $rel_path = 'uploads/evidence/' . $safe_name;

        // Save file record to DB
        $stmt = $db->prepare("INSERT INTO complaint_evidence (complaint_id, file_path, file_name, uploaded_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $orig_name = $_FILES['evidence']['name'][$i];
            $stmt->bind_param('sss', $complaint_id, $rel_path, $orig_name);
            $stmt->execute();
            $stmt->close();
        }

        $uploaded[] = $rel_path;
    } else {
        $errors[] = 'Failed to save: ' . htmlspecialchars($_FILES['evidence']['name'][$i]);
    }
}

$db->close();

echo json_encode([
    'success' => count($uploaded) > 0 || count($errors) === 0,
    'files'   => $uploaded,
    'errors'  => $errors,
    'message' => count($uploaded) . ' file(s) uploaded successfully.'
]);
