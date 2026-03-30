<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];
    $location = $_POST['location'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $website = $_POST['website'];

    $stmt = $conn->prepare("INSERT INTO hospitals 
        (name, location, specialization, contact_info, email, website) 
        VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die('Error preparing the SQL query: ' . $conn->error);
    }

    $stmt->bind_param("ssssss", $name, $location, $specialization, $contact, $email, $website);

    if ($stmt->execute()) {
        echo "
        <script>
            alert('Hospital added successfully!');
            window.location.href='admin_dashboard.php';
        </script>
        ";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>