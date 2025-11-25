<?php
session_start();
include '../db_connect.php';

if (!isset($_POST['appointment_id']) || !isset($_POST['sender']) || !isset($_POST['message'])) {
    die("Missing data");
}

$appointment_id = (int)$_POST['appointment_id'];
$message = trim($_POST['message']);
$sender = $_POST['sender'];

// Decide sender type
if ($sender == "user") {
    if (!isset($_SESSION['user_id'])) { die("Not logged in"); }
    $sender_type = "user";
}
elseif ($sender == "psychologist") {
    if (!isset($_SESSION['p_id'])) { die("Not logged in"); }
    $sender_type = "psychologist";
}
else {
    die("Invalid sender");
}

// INSERT MESSAGE — delivered=0, seen=0
$sql = "INSERT INTO messages (appointment_id, sender_type, message, delivered, seen)
        VALUES (?, ?, ?, 0, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $appointment_id, $sender_type, $message);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "ERROR: " . $stmt->error;
}
