<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

$hospital = [];
if (isset($_GET['hospital_id'])) {
    $hospital_id = $_GET['hospital_id'];
    $result = $conn->query("SELECT * FROM hospitals WHERE hospital_id = $hospital_id");
    $hospital = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $specialization = trim($_POST['specialization']);
    $contact_info = trim($_POST['contact_info']);

    // Basic server-side validation
    if (empty($name) || empty($location) || empty($specialization) || empty($contact_info)) {
        echo "<script>alert('All fields are required!');</script>";
    } else {
        $update_query = "UPDATE hospitals SET name='$name', location='$location', specialization='$specialization', contact_info='$contact_info' WHERE hospital_id=$hospital_id";

        if ($conn->query($update_query)) {
            echo "<script>
                    alert('Hospital updated successfully!');
                    window.location.href = 'admin_dashboard.php';
                  </script>";
        } else {
            echo "Error updating hospital: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Hospital</title>
    <link rel="stylesheet" href="update_hospital.css">
    <script>
        function validateForm() {
            let name = document.forms["updateHospitalForm"]["name"].value.trim();
            let location = document.forms["updateHospitalForm"]["location"].value.trim();
            let specialization = document.forms["updateHospitalForm"]["specialization"].value.trim();
            let contact_info = document.forms["updateHospitalForm"]["contact_info"].value.trim();

            if (name === "" || location === "" || specialization === "" || contact_info === "") {
                alert("All fields are required!");
                return false;
            }

            if (!/^\d{10}$/.test(contact_info)) {
                alert("Please enter a valid 10-digit contact number.");
                return false;
            }

            return true;
        }
    </script>
</head>

<body>
    <h2>Update Hospital</h2>
    <form name="updateHospitalForm" action="" method="POST" onsubmit="return validateForm()">
        <input type="text" name="name" value="<?php echo $hospital['name']; ?>" required>
        <input type="text" name="location" value="<?php echo $hospital['location']; ?>" required>
        <input type="text" name="specialization" value="<?php echo $hospital['specialization']; ?>" required>
        <input type="text" name="contact_info" value="<?php echo $hospital['contact_info']; ?>" required>
        <button type="submit">Update Hospital</button>
    </form>
</body>

</html>
