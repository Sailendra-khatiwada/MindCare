<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) exit;

if (!isset($_POST['appointment_id'])) exit;
$appointment_id = (int)$_POST['appointment_id'];


$sql = "UPDATE messages
        SET delivered = 1, seen = 1
        WHERE appointment_id = ?
        AND sender_type = 'psychologist'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->close();
?>
