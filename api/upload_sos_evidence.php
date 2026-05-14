<?php

include 'db.php';

$sos_id = $_POST['sos_id'];

$crime_type = $_POST['crime_type'];

$description = $_POST['description'];

$sql = "UPDATE sos_alerts
SET crime_type=?,
description=?
WHERE id=?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssi",
    $crime_type,
    $description,
    $sos_id
);

$stmt->execute();

if(isset($_FILES['evidence'])){

    $file = $_FILES['evidence'];

    $filename = time() . "_" . $file['name'];

    move_uploaded_file(
        $file['tmp_name'],
        "../uploads/" . $filename
    );

    $path = "uploads/" . $filename;

    $sql2 = "INSERT INTO sos_evidence
    (sos_id, file_path, file_type)
    VALUES (?, ?, ?)";

    $stmt2 = $conn->prepare($sql2);

    $type = $file['type'];

    $stmt2->bind_param(
        "iss",
        $sos_id,
        $path,
        $type
    );

    $stmt2->execute();
}

echo json_encode([
    "success" => true
]);

?>