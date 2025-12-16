<?php
session_start();
require_once 'db_connect.php'; 

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Get & sanitize input
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
if ($name === '' || $email === '' || $subject === '' || $message === '') {
    header("Location: index.php?status=error");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?status=invalid_email");
    exit;
}

$fullMessage = 
    "Name: {$name}\n" .
    "Email: {$email}\n" .
    "Subject: {$subject}\n" .
    "Message:\n{$message}\n" .
    "--------------------------\n";

// Insert into admin table
$stmt = $conn->prepare("INSERT INTO admin (messages) VALUES (?)");
$stmt->bind_param("s", $fullMessage);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php?status=success");
    exit;
} else {
    $stmt->close();
    header("Location: index.php?status=db_error");
    exit;
}
