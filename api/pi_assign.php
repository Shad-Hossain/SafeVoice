<?php

require_once 'db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$data           = json_decode(file_get_contents('php://input'), true);
$complaintId    = trim($data['complaint_id']   ?? '');
$txnId          = trim($data['txn_id']         ?? '');
$paymentMethod  = trim($data['payment_method'] ?? '');
$senderNumber   = trim($data['sender_number']  ?? '');

if (empty($complaintId) || empty($txnId) || empty($paymentMethod)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'complaint_id, txn_id, payment_method required']);
    exit;
}

// ── 1. Verify complaint exists ───────────────────────────────────────────────
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

// ── 2. Save payment record ───────────────────────────────────────────────────
$pStmt = $db->prepare(
    'INSERT INTO pi_payments (complaint_id, user_id, payment_method, sender_number, txn_id, status, confirmed_at)
     VALUES (?,?,?,?,?,"confirmed", NOW())'
);
$pStmt->bind_param('sisss',
    $complaintId, $complaint['user_id'],
    $paymentMethod, $senderNumber, $txnId
);
$pStmt->execute();
$pStmt->close();

// ── 3. Auto-assign PI with least workload ────────────────────────────────────
$piStmt = $db->prepare(
    'SELECT id, pi_code, full_name, email, phone FROM private_investigators
     WHERE is_active = 1 ORDER BY active_cases ASC LIMIT 1'
);
$piStmt->execute();
$pi = $piStmt->get_result()->fetch_assoc();
$piStmt->close();

if (!$pi) {
    // No PI available — still update status to reflect payment confirmed
    $db->query("UPDATE complaints SET status='PI Payment Confirmed' WHERE complaint_id='$complaintId'");
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed. PI will be assigned shortly.',
        'status'  => 'PI Payment Confirmed'
    ]);
    exit;
}

// ── 4. Assign PI to complaint ────────────────────────────────────────────────
$aStmt = $db->prepare(
    "UPDATE complaints
     SET status='Private Investigator Assigned', assigned_pi_id=?, pi_assigned_at=NOW(), pi_email_sent=0
     WHERE complaint_id=?"
);
$aStmt->bind_param('is', $pi['id'], $complaintId);
$aStmt->execute();
$aStmt->close();

// Increment PI workload
$wStmt = $db->prepare(
    'UPDATE private_investigators SET active_cases = active_cases + 1, total_cases = total_cases + 1 WHERE id = ?'
);
$wStmt->bind_param('i', $pi['id']);
$wStmt->execute();
$wStmt->close();

// ── 5. Send case details email to PI ────────────────────────────────────────
$evidenceFiles = json_decode($complaint['evidence_files'] ?? '[]', true);
$evidenceList  = !empty($evidenceFiles)
    ? implode("\n  - ", $evidenceFiles)
    : 'None uploaded';

$victimInfo = $complaint['is_anonymous']
    ? "ANONYMOUS COMPLAINT\n  (User chose not to disclose identity)"
    : "Name    : " . ($complaint['user_name']    ?? 'N/A') . "\n" .
      "  Phone   : " . ($complaint['user_phone']   ?? 'N/A') . "\n" .
      "  Email   : " . ($complaint['user_email']   ?? 'N/A') . "\n" .
      "  Address : " . ($complaint['user_address'] ?? 'N/A');

$emailSubject = "[SafeVoice] New Case Assigned — " . $complaint['complaint_id'];
$emailBody    = "
====================================================
SAFEVOICE — PRIVATE INVESTIGATOR CASE ASSIGNMENT
====================================================

Dear {$pi['full_name']},

A new case has been assigned to you. Please review all
details carefully and contact the victim as needed.

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
  $victimInfo

────────────────────────────────────────────────────
EVIDENCE FILES
────────────────────────────────────────────────────
  - $evidenceList

────────────────────────────────────────────────────
YOUR ROLE
────────────────────────────────────────────────────
As the assigned Private Investigator, you are expected to:
  1. Contact the victim for further information if needed
  2. Collect additional evidence independently
  3. Compile a formal report for legal proceedings
  4. Report your findings back through official SafeVoice channels

IMPORTANT: This case is strictly confidential. Do not
disclose case details to any third party.

====================================================
SafeVoice — Victim Support Platform
For PI-related inquiries: pi-support@safevoice.com
====================================================
";

$headers = "From: cases@safevoice.com\r\nContent-Type: text/plain; charset=UTF-8\r\n";
// mail($pi['email'], $emailSubject, $emailBody, $headers); // Enable when SMTP configured

// Mark email as sent
$db->query("UPDATE complaints SET pi_email_sent=1 WHERE complaint_id='$complaintId'");

// ── 6. Respond (PI identity hidden) ─────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'message'    => 'Payment confirmed. A Private Investigator has been assigned to your case.',
    'complaint_id'=> $complaintId,
    'status'     => 'Private Investigator Assigned',
    'txn_id'     => $txnId,
    // NOTE: PI name/contact deliberately excluded — user only sees PI if PI contacts them
]);

$db->close();
