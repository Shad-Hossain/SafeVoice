<?php
// api/ocr.php — Server-side OCR proxy (avoids CORS & key issues)
// Place this file in: SafeVoice/api/ocr.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── আপনার OCR.Space API key এখানে দিন ───
define('OCR_API_KEY', 'YOUR_OCR_SPACE_API_KEY_HERE'); // ← এখানে আপনার key দিন
// ──────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['idfile']) || $_FILES['idfile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file     = $_FILES['idfile'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed  = ['jpg', 'jpeg', 'png', 'pdf'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
    exit;
}

// ── OCR.Space API call (server-side, no CORS issue) ──
$ch = curl_init('https://api.ocr.space/parse/image');

$postFields = [
    'apikey'             => OCR_API_KEY,
    'language'           => 'eng',
    'isOverlayRequired'  => 'false',
    'detectOrientation'  => 'true',
    'scale'              => 'true',
    'OCREngine'          => '2',
    'file'               => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
];

curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => "OCR API error (HTTP $httpCode)"]);
    exit;
}

$data = json_decode($response, true);

if (!empty($data['IsErroredOnProcessing']) || empty($data['ParsedResults'])) {
    $errMsg = $data['ErrorMessage'][0] ?? 'OCR processing failed';
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$text = $data['ParsedResults'][0]['ParsedText'] ?? '';

echo json_encode([
    'success' => true,
    'text'    => $text,
]);