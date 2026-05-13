<?php
require_once 'db.php';

$db = getDB();

$full_name = $_POST['full_name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$phone = $_POST['phone'];
$nid = $_POST['nid'];
$address = $_POST['address'];

$code = 'PI-' . rand(1000,9999);

$imageName = '';

if(isset($_FILES['image'])){
    $imageName = time() . '_' . $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/pi/' . $imageName);
}

$stmt = $db->prepare("INSERT INTO private_investigators (
    investigator_code,
    full_name,
    email,
    password,
    phone,
    nid_number,
    address,
    profile_image
) VALUES (?,?,?,?,?,?,?,?)");

$stmt->bind_param(
    'ssssssss',
    $code,
    $full_name,
    $email,
    $password,
    $phone,
    $nid,
    $address,
    $imageName
);

if($stmt->execute()){
    echo json_encode([
        'success' => true,
        'message' => 'PI recruited successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed'
    ]);
}