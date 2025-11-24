<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contact'];

    $stmt = $conn->prepare("INSERT INTO hospitals (name, location, specialization, contact_info) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die('Error preparing the SQL query: ' . $conn->error);
    }
    $stmt->bind_param("ssss", $name, $location, $specialization, $contact);
    if ($stmt->execute()) {
        echo "<script>alert('Hospital added successfully');</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
