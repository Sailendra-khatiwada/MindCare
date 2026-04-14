<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['p_id'])) {
    echo json_encode(['unread' => 0]);
    exit;
}

$p_id = (int) $_SESSION['p_id'];

$sql = "
  SELECT COUNT(*) AS unread
  FROM messages m
  JOIN appointments a ON m.appointment_id = a.appointment_id
  WHERE a.p_id = ? 
    AND m.sender_type = 'user'
    AND m.seen = 0
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $p_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$unread = (int)$row['unread'];

echo json_encode(['unread' => $unread]);
?>