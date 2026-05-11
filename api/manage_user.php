<?php
require_once 'db.php';

// Ensure users table exists
$db = getDB();
$db->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('Active','Suspended','Probation','Banned') DEFAULT 'Active',
    complaints_count INT DEFAULT 0,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Seed sample users if empty
$count = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
if ($count == 0) {
    $db->query("INSERT INTO users (name, email, status, complaints_count) VALUES
        ('Shad Hossain',  'shad@example.com',  'Active',    12),
        ('Tania Begum',   'tania@example.com', 'Active',     7),
        ('Arif Hossain',  'arif@example.com',  'Active',     5),
        ('Nadia Islam',   'nadia@example.com', 'Suspended',  3)");
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — list users
if ($method === 'GET') {
    $result = $db->query("SELECT * FROM users ORDER BY joined_at DESC");
    $users  = [];
    while ($row = $result->fetch_assoc()) $users[] = $row;
    echo json_encode(['success' => true, 'users' => $users]);
    $db->close(); exit;
}

// POST — update user status
if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = intval($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');
    $allowed = ['Active','Suspended','Probation','Banned'];

    if (!$id || !in_array($status, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        $db->close(); exit;
    }

    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => "User status updated to $status"]);
    $stmt->close(); $db->close(); exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
