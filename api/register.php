<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name      = trim($_POST['name']      ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$password  = trim($_POST['password']  ?? '');
$id_type   = trim($_POST['id_type']   ?? '');
$id_number = trim($_POST['id_number'] ?? '');
$location  = trim($_POST['location']  ?? '');

$errors = [];

if (!$name)                                         $errors[] = 'Full name is required.';
if (!$email)                                        $errors[] = 'Email is required.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

$cleanPhone = preg_replace('/\D/', '', $phone);
if (!$phone) {
    $errors[] = 'Phone number is required.';
} elseif (!preg_match('/^(880)?01[3-9]\d{8}$/', $cleanPhone)) {
    $errors[] = 'Enter a valid Bangladesh phone number.';
} else {
    if (strlen($cleanPhone) === 13) $cleanPhone = substr($cleanPhone, 2);
    $phone = $cleanPhone;
}

if (strlen($password) < 8)                                    $errors[] = 'Password must be at least 8 characters.';
if (!in_array($id_type, ['nid','birth_certificate']))         $errors[] = 'ID type must be NID or Birth Certificate.';
if (!$id_number)                                              $errors[] = 'ID number is required.';

if ($id_type === 'nid' && !preg_match('/^\d{10}(\d{7})?$/', $id_number))
    $errors[] = 'NID must be 10 or 17 digits.';
if ($id_type === 'birth_certificate' && !preg_match('/^\d{17}$/', $id_number))
    $errors[] = 'Birth Certificate must be 17 digits.';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$db = getDB();

// Check duplicates
$chk = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$chk->bind_param('s', $email); $chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    $chk->close(); $db->close(); exit;
}
$chk->close();

$chk2 = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
$chk2->bind_param('s', $phone); $chk2->execute();
if ($chk2->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This phone number is already registered.']);
    $chk2->close(); $db->close(); exit;
}
$chk2->close();

$chk3 = $db->prepare("SELECT id FROM users WHERE id_number = ? AND id_type = ? LIMIT 1");
$chk3->bind_param('ss', $id_number, $id_type); $chk3->execute();
if ($chk3->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This ID number is already registered.']);
    $chk3->close(); $db->close(); exit;
}
$chk3->close();

// File uploads
$uploadDir        = __DIR__ . '/../uploads/';
$id_document_path = null;
$profile_photo    = null;
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!empty($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','pdf']) && $_FILES['id_document']['size'] <= 5*1024*1024) {
        $filename = 'id_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['id_document']['tmp_name'], $uploadDir . $filename);
        $id_document_path = 'uploads/' . $filename;
    }
}

if (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['profile_photo']['size'] <= 2*1024*1024) {
        $filename = 'photo_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadDir . $filename);
        $profile_photo = 'uploads/' . $filename;
    }
}

$password_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, id_type, id_number, id_document_path, location, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssssss', $name, $email, $phone, $password_hash, $id_type, $id_number, $id_document_path, $location, $profile_photo);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Try again.']);
    $stmt->close(); $db->close(); exit;
}

$newId = $db->insert_id;
$stmt->close(); $db->close();

echo json_encode([
    'success' => true,
    'message' => 'Registration successful!',
    'user'    => ['id' => $newId, 'name' => $name, 'email' => $email, 'phone' => $phone]
]);