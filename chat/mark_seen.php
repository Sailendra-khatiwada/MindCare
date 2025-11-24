<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) exit;

$appointment_id = $_POST['appointment_id'];

if ($current_user_type == 'user') {
    $sql_delivered = "UPDATE messages 
                      SET delivered = 1 
                      WHERE appointment_id = ? AND sender_type = 'psychologist'";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
?>
