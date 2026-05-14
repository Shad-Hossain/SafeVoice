<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($data['action'] ?? '');

$db = getDB();

// Auto-create password_resets table (email-based)
$db->query("CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    otp_code   VARCHAR(6)   NOT NULL,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Utility: send OTP email via PHPMailer (Gmail SMTP) ───────
function sendOtpEmail(string $toEmail, string $toName, string $otp): bool {
    
    $smtpUser = 'safevoice.noreply@gmail.com';     
    $smtpPass = 'wbfr cmdz jsyj ghtc';   
    $fromName = 'SafeVoice';
    // ════════════════════════════════════════════════════════════

    require_once __DIR__ . '/src/PHPMailer.php';
    require_once __DIR__ . '/src/SMTP.php';
    require_once __DIR__ . '/src/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtpUser, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'SafeVoice — Password Reset OTP';
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:30px;background:#0d1526;color:#fff;border-radius:12px;'>
            <h2 style='color:#4f9eff;margin-bottom:4px;'>SafeVoice</h2>
            <p style='color:#a0b4cc;font-size:13px;margin-top:0'>Password Reset Request</p>
            <hr style='border:1px solid #1e2d4a;margin:20px 0;'>
            <p>Hi <strong>{$toName}</strong>,</p>
            <p>Your one-time password (OTP) for resetting your SafeVoice account password is:</p>
            <div style='text-align:center;margin:24px 0;'>
                <span style='font-size:36px;font-weight:900;letter-spacing:10px;color:#4f9eff;background:#0a1428;padding:16px 24px;border-radius:10px;display:inline-block;'>{$otp}</span>
            </div>
            <p style='color:#a0b4cc;font-size:13px;'>This OTP is valid for <strong style='color:#fff;'>10 minutes</strong>. Do not share it with anyone.</p>
            <p style='color:#a0b4cc;font-size:12px;margin-top:30px;'>If you did not request a password reset, please ignore this email.</p>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// ── ACTION: send_otp ─────────────────────────────────────────
if ($action === 'send_otp') {
    $email = trim(strtolower($data['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        $db->close(); exit;
    }

    // Find user by email
    $chk = $db->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
    $chk->bind_param('s', $email);
    $chk->execute();
    $userRow = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$userRow) {
        // Security: don't reveal whether email exists
        echo json_encode(['success' => true, 'message' => 'If this email is registered, an OTP has been sent.']);
        $db->close(); exit;
    }

    // Rate limit: max 3 OTPs per 10 minutes
    $rate = $db->prepare("SELECT COUNT(*) as c FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $rate->bind_param('s', $email);
    $rate->execute();
    if ((int)$rate->get_result()->fetch_assoc()['c'] >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait 10 minutes.']);
        $rate->close(); $db->close(); exit;
    }
    $rate->close();

    // Invalidate previous OTPs
    $inv = $db->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
    $inv->bind_param('s', $email);
    $inv->execute();
    $inv->close();

    // Generate OTP
    $otp       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);

    $ins = $db->prepare("INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param('sss', $email, $otp, $expiresAt);
    $ins->execute();
    $ins->close();

    // Send email
    $sent = sendOtpEmail($email, $userRow['name'], $otp);

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent to your email!',
    ]);
    $db->close(); exit;
}

// ── ACTION: verify_otp ───────────────────────────────────────
if ($action === 'verify_otp') {
    $email = trim(strtolower($data['email'] ?? ''));
    $otp   = trim($data['otp'] ?? '');

    $stmt = $db->prepare(
        "SELECT id FROM password_resets
         WHERE email = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
        $stmt->close(); $db->close(); exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'OTP verified.']);
    $db->close(); exit;
}

// ── ACTION: reset ────────────────────────────────────────────
if ($action === 'reset') {
    $email  = trim(strtolower($data['email']        ?? ''));
    $otp    = trim($data['otp']                     ?? '');
    $newpwd = trim($data['new_password']            ?? '');

    if (strlen($newpwd) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        $db->close(); exit;
    }

    // Verify OTP one final time
    $stmt = $db->prepare(
        "SELECT id FROM password_resets
         WHERE email = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('ss', $email, $otp);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'OTP expired. Please start over.']);
        $stmt->close(); $db->close(); exit;
    }
    $stmt->close();

    // Update password
    $hash   = password_hash($newpwd, PASSWORD_BCRYPT);
    $update = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
    $update->bind_param('ss', $hash, $email);
    $update->execute();

    if ($update->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Account not found.']);
        $update->close(); $db->close(); exit;
    }
    $update->close();

    // Mark OTP as used
    $mark = $db->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND otp_code = ?");
    $mark->bind_param('ss', $email, $otp);
    $mark->execute();
    $mark->close();

    echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
    $db->close(); exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
$db->close();
