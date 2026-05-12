<?php

require_once 'db.php';

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: fetch complaints 
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
            unset($row['assigned_officer_code']); // admin must NOT see officer
            echo json_encode(['success' => true, 'complaint' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Complaint not found']);
        }
        $stmt->close();
        $db->close();
        exit;
    }

    // All complaints with optional filters — never expose officer code
    $where  = [];
    $params = [];
    $types  = '';

    $status = $_GET['status'] ?? '';
    if (!empty($status)) {
        $where[]  = 'status = ?';
        $params[] = $status;
        $types   .= 's';
    }

    $type = $_GET['type'] ?? '';
    if (!empty($type)) {
        $where[]  = 'type = ?';
        $params[] = $type;
        $types   .= 's';
    }

    $sql = 'SELECT id, complaint_id, type, incident_date, location, description, is_anonymous, status, submitted_at, updated_at FROM complaints';
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

    // Stats
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

// ── POST: update status 
if ($method === 'POST') {

    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = trim($data['complaint_id'] ?? '');
    $status = trim($data['status']       ?? '');

    $allowed = ['Submitted', 'Under Review', 'Private Investigator Assigned', 'Investigation', 'Resolved', 'Rejected'];
    if (empty($id) || !in_array($status, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid complaint_id or status']);
        exit;
    }

    // ── Blind officer assignment 
    if ($status === 'Private Investigator Assigned') {

        $offStmt = $db->prepare(
            'SELECT officer_code FROM officers WHERE is_active = 1 ORDER BY assigned_cases ASC LIMIT 1'
        );
        $offStmt->execute();
        $offResult = $offStmt->get_result();
        $officer   = $offResult->fetch_assoc();
        $offStmt->close();

        if (!$officer) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'No active officers available']);
            exit;
        }

        $officerCode = $officer['officer_code'];

        $stmt = $db->prepare(
            'UPDATE complaints SET status = ?, assigned_officer_code = ? WHERE complaint_id = ?'
        );
        $stmt->bind_param('sss', $status, $officerCode, $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $incStmt = $db->prepare('UPDATE officers SET assigned_cases = assigned_cases + 1 WHERE officer_code = ?');
            $incStmt->bind_param('s', $officerCode);
            $incStmt->execute();
            $incStmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Status updated to Private Investigator Assigned. Payment notification sent to user.'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Complaint not found or status unchanged']);
        }

        $stmt->close();
        $db->close();
        exit;
    }

    // ── Regular status update 
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