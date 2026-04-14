<?php
session_start();
include '../db_connect.php';

$secret_key = "my_super_secret_key_2026";
$secret_iv  = "my_secret_iv_2026";
$encrypt_method = "AES-256-CBC";

$key = hash('sha256', $secret_key);
$iv  = substr(hash('sha256', $secret_iv), 0, 16);

if (!isset($_GET['appointment_id'])) {
    die("Missing appointment_id");
}

$appointment_id = (int)$_GET['appointment_id'];

$isUser  = isset($_SESSION['user_id']);
$isPsych = isset($_SESSION['p_id']) || isset($_SESSION['psychologist_id']) || isset($_SESSION['pid']);

if ($isPsych) {
    $update_sql = "UPDATE messages 
                   SET delivered = 1
                   WHERE appointment_id = ?
                   AND sender_type = 'user'
                   AND delivered = 0";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT msg_id, sender_type, message, delivered, seen, created_at
        FROM messages
        WHERE appointment_id = ?
        ORDER BY msg_id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $row['message'] = openssl_decrypt($row['message'], $encrypt_method, $key, 0, $iv);
    $messages[] = $row;
}

$stmt->close();

header("Content-Type: application/json");
echo json_encode($messages);
?>
