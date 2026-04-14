<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['p_id'])) exit;

$appointment_id = $_POST['appointment_id'];

// Only mark USER messages as seen, not psychologist's own messages
$sql = "UPDATE messages
        SET seen = 1
        WHERE appointment_id = ?
        AND sender_type = 'user'
        AND seen = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->close();
?>