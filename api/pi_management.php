<?php

require_once 'db.php';
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── Super Admin Auth Check ──────────────────────────────────────────────────
// All requests must include super admin credentials in header or body
function verifySuperAdmin($db, $data = []) {
    $username = $data['sa_username'] ?? ($_SERVER['HTTP_X_SA_USER'] ?? '');
    $password = $data['sa_password'] ?? ($_SERVER['HTTP_X_SA_PASS'] ?? '');
    if (empty($username) || empty($password)) return false;
    $stmt = $db->prepare('SELECT password_hash FROM super_admins WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && password_verify($password, $row['password_hash']);
}

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $saUser = $_GET['sa_username'] ?? '';
    $saPass = $_GET['sa_password'] ?? '';
    if (!verifySuperAdmin($db, ['sa_username' => $saUser, 'sa_password' => $saPass])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Super admin access required']);
        exit;
    }

    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $result = $db->query(
            'SELECT id, pi_code, full_name, email, phone, address, nid_number,
                    photo_url, nid_photo_url, login_email, is_active,
                    active_cases, total_cases, joined_at, notes
             FROM private_investigators ORDER BY active_cases ASC'
        );
        $pis = [];
        while ($row = $result->fetch_assoc()) $pis[] = $row;
        echo json_encode(['success' => true, 'investigators' => $pis]);
        exit;
    }

    if ($action === 'get') {
        $id   = intval($_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM private_investigators WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pi   = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($pi) echo json_encode(['success' => true, 'pi' => $pi]);
        else { http_response_code(404); echo json_encode(['success' => false, 'message' => 'PI not found']); }
        exit;
    }

    if ($action === 'stats') {
        $result = $db->query(
            'SELECT id, pi_code, full_name, is_active, active_cases, total_cases FROM private_investigators'
        );
        $stats = [];
        while ($row = $result->fetch_assoc()) $stats[] = $row;
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── POST ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if (!verifySuperAdmin($db, $data)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Super admin access required']);
        exit;
    }

    // ── Create / Recruit New PI ───────────────────────────────────────────
    if ($action === 'create') {
        $required = ['full_name','email','phone','address','nid_number','login_email'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field '$f' is required"]);
                exit;
            }
        }

        // Auto-generate PI code
        $maxRes = $db->query('SELECT MAX(id) as max_id FROM private_investigators');
        $maxRow = $maxRes->fetch_assoc();
        $nextId = ($maxRow['max_id'] ?? 0) + 1;
        $piCode = 'PI-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        // Hash initial password
        $rawPassword = $data['initial_password'] ?? ('PI@' . rand(10000,99999));
        $passHash    = password_hash($rawPassword, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO private_investigators
                (pi_code, full_name, email, phone, address, nid_number,
                 photo_url, nid_photo_url, login_email, password_hash, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $photoUrl    = $data['photo_url']     ?? '';
        $nidPhotoUrl = $data['nid_photo_url'] ?? '';
        $notes       = $data['notes']         ?? '';
        $loginEmail  = $data['login_email'];
        $stmt->bind_param('sssssssssss',
            $piCode,
            $data['full_name'], $data['email'], $data['phone'],
            $data['address'],   $data['nid_number'],
            $photoUrl, $nidPhotoUrl,
            $loginEmail, $passHash, $notes
        );

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();

            // Send welcome email with credentials
            sendPIWelcomeEmail($data['email'], $data['full_name'], $piCode, $loginEmail, $rawPassword);

            echo json_encode([
                'success'          => true,
                'message'          => 'Private Investigator recruited successfully',
                'pi_code'          => $piCode,
                'pi_id'            => $newId,
                'login_email'      => $loginEmail,
                'initial_password' => $rawPassword
            ]);
        } else {
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
        }
        exit;
    }

    // ── Update PI ─────────────────────────────────────────────────────────
    if ($action === 'update') {
        $id = intval($data['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'PI ID required']); exit; }
        $stmt = $db->prepare(
            'UPDATE private_investigators SET full_name=?, email=?, phone=?, address=?,
             nid_number=?, photo_url=?, nid_photo_url=?, notes=? WHERE id=?'
        );
        $stmt->bind_param('ssssssssi',
            $data['full_name'], $data['email'], $data['phone'], $data['address'],
            $data['nid_number'], $data['photo_url'], $data['nid_photo_url'], $data['notes'], $id
        );
        if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'PI updated']);
        else echo json_encode(['success' => false, 'message' => 'Update failed']);
        $stmt->close();
        exit;
    }

    // ── Toggle Active/Inactive ────────────────────────────────────────────
    if ($action === 'toggle') {
        $id = intval($data['id'] ?? 0);
        $stmt = $db->prepare('UPDATE private_investigators SET is_active = 1 - is_active WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'PI status toggled']);
        exit;
    }

    // ── Delete PI ─────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = intval($data['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM private_investigators WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'PI removed']);
        exit;
    }

    // ── Change PI Password ────────────────────────────────────────────────
    if ($action === 'change_password') {
        $id          = intval($data['id'] ?? 0);
        $newPassword = $data['new_password'] ?? '';
        if (!$id || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID and password (min 6 chars) required']);
            exit;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE private_investigators SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── Email Helper ─────────────────────────────────────────────────────────────
function sendPIWelcomeEmail($to, $name, $piCode, $loginEmail, $password) {
    $subject = "SafeVoice — Welcome, Private Investigator $piCode";
    $body    = "
Dear $name,

You have been recruited as a Private Investigator for SafeVoice.

Your PI Code  : $piCode
Login Email   : $loginEmail
Password      : $password

Please change your password after first use.

Case notifications will be sent to this email with full complaint details,
victim contact information, and all uploaded evidence.

SafeVoice Team
    ";
    $headers = "From: noreply@safevoice.com\r\nContent-Type: text/plain; charset=UTF-8";
    // mail($to, $subject, $body, $headers); // Uncomment when SMTP is configured
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
$db->close();
