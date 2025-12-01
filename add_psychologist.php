<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Read values safely
    $name = $_POST['psychologist_name'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $specialization = $_POST['specialization'];
    $location = $_POST['location'];
    $education = $_POST['education'];

    $min_fee = (float)$_POST['min_fee'];
    $max_fee = (float)$_POST['max_fee'];
    $office_start = $_POST['office_start'];
    $office_end = $_POST['office_end'];
    $contact_info = $_POST['contact_info'];

    // IMPORTANT — match form input name!
    $description = $_POST['description'] ?? "";
    $experties = $_POST['AreaOfExperties'] ?? "";

    if (!$email) {
        echo "<script>alert('Invalid email address');</script>";
        exit;
    }

    // Prepare SQL
    $sql = "INSERT INTO psychologist 
        (username, email, password, specialization, location, education, 
         min_fee, max_fee, office_start, office_end, contact_info, description, AreaOfExperties)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssddsssss",
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
        $description,
        $experties
    );

    if ($stmt->execute()) {
        echo "<script>alert('Psychologist added successfully');</script>";
        header('Location: admin_dashboard.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
