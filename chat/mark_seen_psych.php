<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['p_id'])) exit;

$appointment_id = $_POST['appointment_id'];

if ($current_user_type == 'psychologist') {
    $sql_seen = "UPDATE messages 
                 SET delivered = 1, seen = 1 
                 WHERE appointment_id = ? AND sender_type = 'user'";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
?>
