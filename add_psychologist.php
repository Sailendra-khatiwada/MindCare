<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['psychologist_name'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    // $password = password_hash($password, PASSWORD_BCRYPT);
    $specialization = $_POST['specialization'];
    $location = $_POST['location'];
    $education = $_POST['education'];
    $min_fee = $_POST['min_fee'];
    $max_fee = $_POST['max_fee'];
    $contact_info = $_POST['contact_info'];
    $description = $_POST['description'];
    $office_start = $_POST['office_start'];
    $office_end = $_POST['office_end'];

    if (!$email) {
        echo "<script>alert('Invalid email address');</script>";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO psychologist 
    (username, email, password, specialization, location, education, min_fee, max_fee, office_start, office_end, contact_info, description)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssddssss",
        $name,
        $email,
        $password,
        $specialization,
        $location,
        $education,
        $min_fee,
        $max_fee,
        $office_start,
        $office_end,
        $contact_info,
        $description
    );

    if ($stmt->execute()) {
        echo "<script>alert('Psychologist added successfully');</script>";
        header('Location: admin_dashboard.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
