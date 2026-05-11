<?php
// =============================================
// SafeVoice — Complaints API (Admin)
// GET  /api/complaints.php              → list all
// GET  /api/complaints.php?id=SV-xxx   → single complaint
// POST /api/complaints.php             → update status
// =============================================

require_once 'db.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: fetch complaints ─────────────────────
if ($method === 'GET') {

    $id = $_GET['id'] ?? '';

    // Single complaint by ID
    if (!empty($id)) {
        $stmt = $db->prepare('SELECT * FROM complaints WHERE complaint_id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();

        if ($row) {
            echo json_encode(['success' => true, 'complaint' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        }
        $stmt->close();
        $db->close();
        exit;
    }

    // All complaints — with optional filters
    $where  = [];
    $params = [];
    $types  = '';

    // Filter by status
    $status = $_GET['status'] ?? '';
    if (!empty($status)) {
        $where[]  = 'status = ?';
        $params[] = $status;
        $types   .= 's';
    }

    // Filter by type
    $type = $_GET['type'] ?? '';
    if (!empty($type)) {
        $where[]  = 'type = ?';
        $params[] = $type;
        $types   .= 's';
    }

    $sql = 'SELECT * FROM complaints';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY submitted_at DESC';

    $stmt = $db->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result     = $stmt->get_result();
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }

    // Stats summary
    $stats = [];
    $sr = $db->query('SELECT status, COUNT(*) as cnt FROM complaints GROUP BY status');
    while ($row = $sr->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['cnt'];
    }
    $total = array_sum($stats);

    echo json_encode([
        'success'    => true,
        'complaints' => $complaints,
        'total'      => $total,
        'stats'      => $stats
    ]);

    $stmt->close();
    $db->close();
    exit;
}

// ── POST: update status ───────────────────────
if ($method === 'POST') {

    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = trim($data['complaint_id'] ?? '');
    $status = trim($data['status']       ?? '');

    $allowed = ['Submitted', 'Under Review', 'Officer Assigned', 'Resolved', 'Rejected'];
    if (empty($id) || !in_array($status, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid complaint_id or status']);
        exit;
    }

    $stmt = $db->prepare('UPDATE complaints SET status = ? WHERE complaint_id = ?');
    $stmt->bind_param('ss', $status, $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated to ' . $status]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Complaint not found or status unchanged']);
    }

    $stmt->close();
    $db->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
