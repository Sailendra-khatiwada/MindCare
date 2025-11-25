<?php
session_start();
include '../db_connect.php';

if (!isset($_GET['appointment_id'])) {
    die("Missing appointment_id");
}
$appointment_id = (int)$_GET['appointment_id'];
$isUser  = isset($_SESSION['user_id']);
$isPsych = isset($_SESSION['p_id']) || isset($_SESSION['psychologist_id']) || isset($_SESSION['pid']);

$sql = "SELECT msg_id, sender_type, message, delivered, seen 
        FROM messages 
        WHERE appointment_id = ?
        ORDER BY msg_id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

if ($isUser) {
    $sql = "UPDATE messages 
            SET delivered = 1
            WHERE appointment_id = ?
            AND sender_type = 'psychologist'";
} elseif ($isPsych) {
    $sql = "UPDATE messages 
            SET delivered = 1
            WHERE appointment_id = ?
            AND sender_type = 'user'";
}
if (isset($sql)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();
}

header("Content-Type: application/json");
echo json_encode($messages);
