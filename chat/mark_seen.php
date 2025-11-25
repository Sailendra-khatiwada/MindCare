<?php
session_start();
include '../db_connect.php';

// USER must be logged in
if (!isset($_SESSION['user_id'])) exit;

// Ensure appointment ID exists
if (!isset($_POST['appointment_id'])) exit;
$appointment_id = (int)$_POST['appointment_id'];

$sql = "UPDATE messages
        SET delivered = 1
        WHERE appointment_id = ?
        AND sender_type = 'user'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->close();

$sql2 = "UPDATE messages
         SET seen = 1
         WHERE appointment_id = ?
         AND sender_type = 'psychologist'";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $appointment_id);
$stmt2->execute();
$stmt2->close();
?>
