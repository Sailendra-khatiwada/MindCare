<?php
session_start();
header('Content-Type: application/json');

include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$sql = "
  SELECT COUNT(*) AS unread
  FROM messages m
  JOIN appointments a ON m.appointment_id = a.appointment_id
  WHERE a.user_id = ? 
    AND m.sender_type = 'psychologist'
    AND m.seen = 0
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'sql_prepare_failed', 'details' => $conn->error]);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$unread = (int)$row['unread'];

echo json_encode(['unread' => $unread]);
