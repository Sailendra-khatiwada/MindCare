<?php
session_start();
include '../db_connect.php';

if (!isset($_POST['msg_id'])) {
    die("Missing msg_id");
}

$msg_id = (int)$_POST['msg_id'];

$sql = "UPDATE messages SET delivered = 1 WHERE msg_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $msg_id);
$stmt->execute();
$stmt->close();

echo "OK";
?>
