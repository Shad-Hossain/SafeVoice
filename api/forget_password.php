<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($data['action'] ?? '');

$db = getDB();

$db->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function normalizePhone($raw) {
    $clean = preg_replace('/\D/', '', $raw);
    if (strlen($clean) === 13 && substr($clean, 0, 3) === '880') $clean = '0' . substr($clean, 3);
    return $clean;
}

function sendSMS($phone, $message) {
     
    
    
     $url  = 'https://sms.sslwireless.com/pushapi/dynamic/server.php';
     $post = http_build_query([
         'api_token' => 'YOUR_SSL_WIRELESS_API_TOKEN',
         'sid'       => 'YOUR_SENDER_ID',
         'msisdn'    => '88' . $phone,
         'sms'       => $message,
         'csmsid'    => uniqid(),
     ]);
     $ch = curl_init($url);
     curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$post, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
     curl_exec($ch); curl_close($ch);
   
    error_log("[SafeVoice SMS] To: $phone | Msg: $message");
    return true;
}

// ── ACTION: send_otp ──────────────────────────────────
if ($action === 'send_otp') {
    $phone = normalizePhone($data['phone'] ?? '');
    if (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Enter a valid Bangladesh phone number.']);
        $db->close(); exit;
    }

    $chk = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $chk->bind_param('s', $phone); $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        // Security: don't reveal if phone exists
        echo json_encode(['success' => true, 'message' => 'If registered, OTP has been sent.']);
        $chk->close(); $db->close(); exit;
    }
    $chk->close();

    // Rate limit: 3 OTP per 10 minutes
    $rate = $db->prepare("SELECT COUNT(*) as c FROM password_resets WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $rate->bind_param('s', $phone); $rate->execute();
    if ($rate->get_result()->fetch_assoc()['c'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Wait 10 minutes.']);
        $rate->close(); $db->close(); exit;
    }
    $rate->close();

    // Invalidate old OTPs
    $inv = $db->prepare("UPDATE password_resets SET used = 1 WHERE phone = ? AND used = 0");
    $inv->bind_param('s', $phone); $inv->execute(); $inv->close();

    $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);

    $ins = $db->prepare("INSERT INTO password_resets (phone, otp_code, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param('sss', $phone, $otp, $expiresAt); $ins->execute(); $ins->close();

    sendSMS($phone, "SafeVoice: Your OTP is $otp. Valid 10 minutes. Do not share.");

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent!',
        'dev_otp' => $otp   // ← PRODUCTION-এ এই লাইনটা DELETE করো
    ]);
    $db->close(); exit;
}

// ── ACTION: verify_otp ───────────────────────────────
if ($action === 'verify_otp') {
    $phone = normalizePhone($data['phone'] ?? '');
    $otp   = trim($data['otp'] ?? '');
    $stmt  = $db->prepare("SELECT id FROM password_resets WHERE phone = ? AND otp_code = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $phone, $otp); $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
        $stmt->close(); $db->close(); exit;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'OTP verified.']);
    $db->close(); exit;
}

// ── ACTION: reset ────────────────────────────────────
if ($action === 'reset') {
    $phone    = normalizePhone($data['phone'] ?? '');
    $otp      = trim($data['otp']          ?? '');
    $newpwd   = trim($data['new_password'] ?? '');

    if (strlen($newpwd) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        $db->close(); exit;
    }

    $stmt = $db->prepare("SELECT id FROM password_resets WHERE phone = ? AND otp_code = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $phone, $otp); $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
        $stmt->close(); $db->close(); exit;
    }
    $stmt->close();

    $hash   = password_hash($newpwd, PASSWORD_BCRYPT);
    $update = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE phone = ?");
    $update->bind_param('ss', $hash, $phone); $update->execute();
    if ($update->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No account with this phone.']);
        $update->close(); $db->close(); exit;
    }
    $update->close();

    $mark = $db->prepare("UPDATE password_resets SET used = 1 WHERE phone = ? AND otp_code = ?");
    $mark->bind_param('ss', $phone, $otp); $mark->execute(); $mark->close();

    echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
    $db->close(); exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
$db->close();