<?php
session_start();
include '../db_connect.php';

if (!isset($_GET['appointment_id'])) {
    die("Missing appointment_id");
}

$appointment_id = (int)$_GET['appointment_id'];

// Detect viewer (to update seen)
$isUser = isset($_SESSION['user_id']);
$isPsych = isset($_SESSION['p_id']) || isset($_SESSION['psychologist_id']) || isset($_SESSION['pid']);

// 1️⃣ FETCH ALL MESSAGES
$sql = "SELECT msg_id, sender_type, message, delivered, seen 
        FROM messages 
        WHERE appointment_id = ?
        ORDER BY msg_id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
$lastMsgId = 0;

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
    $lastMsgId = $row['msg_id'];
}
$stmt->close();

// 2️⃣ MARK ALL RECEIVED MESSAGES AS "DELIVERED"
$sql = "UPDATE messages 
        SET delivered = 1 
        WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->close();

// 3️⃣ MARK MESSAGES AS "SEEN" BASED ON WHO IS VIEWING
if ($isUser) {
    // User sees psychologist messages
    $sql = "UPDATE messages 
            SET seen = 1 
            WHERE appointment_id = ?
            AND sender_type = 'psychologist'";
}
elseif ($isPsych) {
    // Psychologist sees user messages
    $sql = "UPDATE messages 
            SET seen = 1 
            WHERE appointment_id = ?
            AND sender_type IN ('user', 'patient')";
}

if ($isUser || $isPsych) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();
}

// 4️⃣ RETURN MESSAGES AS JSON
header("Content-Type: application/json");
echo json_encode($messages);
?>
