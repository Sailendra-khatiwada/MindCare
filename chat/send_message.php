<?php
session_start();
include '../db_connect.php';

$secret_key = "my_super_secret_key_2026";  
$secret_iv  = "my_secret_iv_2026";
$encrypt_method = "AES-256-CBC";

$key = hash('sha256', $secret_key);
$iv  = substr(hash('sha256', $secret_iv), 0, 16);

if (!isset($_POST['appointment_id']) || !isset($_POST['sender']) || !isset($_POST['message'])) {
    die("Missing data");
}

$appointment_id = (int)$_POST['appointment_id'];
$message = trim($_POST['message']);
$sender = $_POST['sender'];

if ($sender == "user") {
    if (!isset($_SESSION['user_id'])) { die("Not logged in"); }
    $sender_type = "user";
    $sender_id   = $_SESSION['user_id'];
}
elseif ($sender == "psychologist") {
    if (!isset($_SESSION['p_id'])) { die("Not logged in"); }
    $sender_type = "psychologist";
    $sender_id   = $_SESSION['p_id'];
}
else {
    die("Invalid sender");
}

$encrypted_message = openssl_encrypt($message, $encrypt_method, $key, 0, $iv);

$sql = "INSERT INTO messages (appointment_id, sender_id, sender_type, message, delivered, seen)
        VALUES (?, ?, ?, ?, 0, 0)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $appointment_id, $sender_id, $sender_type, $encrypted_message);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "ERROR: " . $stmt->error;
}
?>
