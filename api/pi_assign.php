<?php
require_once 'db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$data          = json_decode(file_get_contents('php://input'), true);
$complaintId   = trim($data['complaint_id']   ?? '');
$txnId         = trim($data['txn_id']         ?? '');
$paymentMethod = trim($data['payment_method'] ?? '');
$senderNumber  = trim($data['sender_number']  ?? '');

if (empty($complaintId) || empty($txnId) || empty($paymentMethod)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'complaint_id, txn_id, payment_method required']);
    exit;
}

// 1. Fetch complaint
$cStmt = $db->prepare(
    'SELECT id, complaint_id, user_id, user_name, user_phone, user_email, user_address,
            type, incident_date, location, description, evidence_files, is_anonymous, status
     FROM complaints WHERE complaint_id = ?'
);
$cStmt->bind_param('s', $complaintId);
$cStmt->execute();
$complaint = $cStmt->get_result()->fetch_assoc();
$cStmt->close();

if (!$complaint) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Complaint not found']);
    exit;
}

// 2. Record payment
$pStmt = $db->prepare(
    'INSERT INTO pi_payments (complaint_id, user_id, payment_method, sender_number, txn_id, status, confirmed_at)
     VALUES (?,?,?,?,?,"confirmed", NOW())'
);
$pStmt->bind_param('sisss', $complaintId, $complaint['user_id'], $paymentMethod, $senderNumber, $txnId);
$pStmt->execute();
$pStmt->close();

// Update notification response
$db->query("UPDATE pi_notifications SET user_response='accepted', responded_at=NOW()
            WHERE complaint_id='" . $db->real_escape_string($complaintId) . "'
            ORDER BY sent_at DESC LIMIT 1");

// 3. Select PI with lowest workload
$piStmt = $db->prepare(
    'SELECT id, pi_code, full_name, login_email, phone, email
     FROM private_investigators
     WHERE is_active = 1
     ORDER BY active_cases ASC, total_cases ASC
     LIMIT 1'
);
$piStmt->execute();
$pi = $piStmt->get_result()->fetch_assoc();
$piStmt->close();

if (!$pi) {
    $db->query("UPDATE complaints SET status='PI Payment Confirmed' WHERE complaint_id='" . $db->real_escape_string($complaintId) . "'");
    echo json_encode(['success' => true, 'message' => 'Payment confirmed. PI will be assigned shortly.', 'status' => 'PI Payment Confirmed']);
    $db->close();
    exit;
}

// 4. Assign PI
$aStmt = $db->prepare(
    "UPDATE complaints SET status='Private Investigator Assigned', assigned_pi_id=?, pi_assigned_at=NOW(), pi_email_sent=0 WHERE complaint_id=?"
);
$aStmt->bind_param('is', $pi['id'], $complaintId);
$aStmt->execute();
$aStmt->close();

// Increment workload
$wStmt = $db->prepare('UPDATE private_investigators SET active_cases = active_cases + 1, total_cases = total_cases + 1 WHERE id = ?');
$wStmt->bind_param('i', $pi['id']);
$wStmt->execute();
$wStmt->close();

// 5. Build email body
$evidenceFiles = json_decode($complaint['evidence_files'] ?? '[]', true);
$evidenceList  = !empty($evidenceFiles) ? implode("\n  - ", array_map('basename', (array)$evidenceFiles)) : 'No files uploaded';

if ($complaint['is_anonymous']) {
    $victimInfo = "ANONYMOUS COMPLAINT\n  (Victim chose not to disclose identity)";
} else {
    $victimInfo = "Name    : " . ($complaint['user_name']    ?? 'N/A') . "\n" .
                  "  Phone   : " . ($complaint['user_phone']   ?? 'N/A') . "\n" .
                  "  Email   : " . ($complaint['user_email']   ?? 'N/A') . "\n" .
                  "  Address : " . ($complaint['user_address'] ?? 'N/A');
}

$payMethods = ['bkash'=>'bKash','nagad'=>'Nagad','rocket'=>'Rocket','bank'=>'Bank Transfer'];
$payLabel   = $payMethods[$paymentMethod] ?? strtoupper($paymentMethod);

$emailSubject = "[SafeVoice] New Case Assigned — " . $complaint['complaint_id'];
$emailBody    = "
====================================================
  SAFEVOICE — PRIVATE INVESTIGATOR CASE ASSIGNMENT
====================================================

Dear {$pi['full_name']},

A new case has been assigned to you. Victim has confirmed
PI service and payment has been verified.

────────────────────────────────────────────────────
  CASE DETAILS
────────────────────────────────────────────────────
  Complaint ID  : {$complaint['complaint_id']}
  Case Type     : {$complaint['type']}
  Incident Date : {$complaint['incident_date']}
  Location      : {$complaint['location']}

  Description:
    {$complaint['description']}

────────────────────────────────────────────────────
  VICTIM / COMPLAINANT INFORMATION
────────────────────────────────────────────────────
  {$victimInfo}

────────────────────────────────────────────────────
  PAYMENT VERIFICATION
────────────────────────────────────────────────────
  Method        : {$payLabel}
  Transaction ID: {$txnId}
  Sender Number : {$senderNumber}
  Amount        : 1,000 BDT

────────────────────────────────────────────────────
  EVIDENCE FILES
────────────────────────────────────────────────────
  - {$evidenceList}

────────────────────────────────────────────────────
  YOUR RESPONSIBILITIES
────────────────────────────────────────────────────
  1. Contact the victim using the details above
  2. Gather additional evidence independently
  3. Compile a formal investigation report
  4. Prepare documentation for legal proceedings

  IMPORTANT: This case is STRICTLY CONFIDENTIAL.
  Do not disclose any details to third parties.
  Your identity remains hidden from the admin panel.

====================================================
  SafeVoice Victim Support Platform
  PI Support: pi-support@safevoice.com
====================================================
";

$headers  = "From: cases@safevoice.com\r\n";
$headers .= "Reply-To: pi-support@safevoice.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$emailSent = mail($pi['login_email'], $emailSubject, $emailBody, $headers);
$db->query("UPDATE complaints SET pi_email_sent=" . ($emailSent ? 1 : 0) . " WHERE complaint_id='" . $db->real_escape_string($complaintId) . "'");

// 6. Return — PI identity NEVER disclosed
echo json_encode([
    'success'      => true,
    'message'      => 'Payment confirmed. A Private Investigator has been assigned to your case.',
    'complaint_id' => $complaintId,
    'status'       => 'Private Investigator Assigned',
    'txn_id'       => $txnId
]);

$db->close();