<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

$psychologist = [];
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];
    $result = $conn->query("SELECT * FROM psychologist WHERE p_id = $p_id");
    $psychologist = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['psychologist_name']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $location = trim($_POST['location']);
    $education = trim($_POST['education']);
    $min_fee = trim($_POST['min_fee']);
    $max_fee = trim($_POST['max_fee']);
    $office_start = trim($_POST['office_start']);
    $office_end = trim($_POST['office_end']);
    $contact_info = trim($_POST['contact_info']);

    if (empty($username) || empty($email) || empty($specialization) || empty($location) || empty($education) || empty($min_fee) || empty($max_fee) || empty($office_start) || empty($office_end) || empty($contact_info)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!');</script>";
    } elseif (!preg_match("/^\d{10}$/", $contact_info)) {
        echo "<script>alert('Please enter a valid 10-digit contact number!');</script>";
    } elseif ($min_fee < 0 || $max_fee < 0 || $min_fee > $max_fee) {
        echo "<script>alert('Invalid fee structure! Minimum fee cannot be greater than the maximum fee.');</script>";
    } else {
        $update_query = "UPDATE psychologist SET username='$username', email='$email', specialization='$specialization', location='$location', education='$education', min_fee='$min_fee', max_fee='$max_fee', office_start='$office_start', office_end='$office_end', contact_info='$contact_info' WHERE p_id=$p_id";

        if ($conn->query($update_query)) {
            echo "<script>
                    alert('Psychologist details updated successfully!');
                    window.location.href = 'admin_dashboard.php';
                  </script>";
        } else {
            echo "Error updating psychologist: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Psychologist</title>
    <link rel="stylesheet" href="update_psychologist.css">
    <script>
        function validateForm() {
            let name = document.forms["updatePsychologistForm"]["psychologist_name"].value.trim();
            let email = document.forms["updatePsychologistForm"]["email"].value.trim();
            let specialization = document.forms["updatePsychologistForm"]["specialization"].value.trim();
            let location = document.forms["updatePsychologistForm"]["location"].value.trim();
            let education = document.forms["updatePsychologistForm"]["education"].value.trim();
            let min_fee = document.forms["updatePsychologistForm"]["min_fee"].value.trim();
            let max_fee = document.forms["updatePsychologistForm"]["max_fee"].value.trim();
            let office_start = document.forms["updatePsychologistForm"]["office_start"].value.trim();
            let office_end = document.forms["updatePsychologistForm"]["office_end"].value.trim();
            let contact_info = document.forms["updatePsychologistForm"]["contact_info"].value.trim();

            min_fee = parseFloat(min_fee);
            max_fee = parseFloat(max_fee);

            if (isNaN(min_fee) || isNaN(max_fee)) {
                alert("Please enter valid numerical values for fees.");
                return false;
            }

            if (min_fee <= 0 || max_fee <= 0) {
                alert("Fees cannot be zero or negative.");
                return false;
            }


            if (name === "" || email === "" || specialization === "" || location === "" || education === "" || min_fee === "" || max_fee === "" || office_start === "" || office_end === "" || contact_info === "") {
                alert("All fields are required!");
                return false;
            }

            let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
            if (!emailPattern.test(email)) {
                alert("Please enter a valid email address.");
                return false;
            }

            if (!/^\d{10}$/.test(contact_info)) {
                alert("Please enter a valid 10-digit contact number.");
                return false;
            }

            if (min_fee < 0 || max_fee < 0 || parseFloat(min_fee) > parseFloat(max_fee)) {
                alert("Invalid fee structure! Minimum fee cannot be greater than the maximum fee.");
                return false;
            }

            return true;
        }
    </script>
</head>

<body>
    <form name="updatePsychologistForm" action="" method="POST" onsubmit="return validateForm()">
        <input type="text" name="psychologist_name" value="<?php echo $psychologist['username']; ?>" required>
        <input type="email" name="email" value="<?php echo $psychologist['email']; ?>" required>
        <input type="text" name="specialization" value="<?php echo $psychologist['specialization']; ?>" required>
        <input type="text" name="location" value="<?php echo $psychologist['location']; ?>" required>
        <input type="text" name="education" value="<?php echo $psychologist['education']; ?>" required>
        <input type="number" name="min_fee" value="<?php echo $psychologist['min_fee']; ?>" required>
        <input type="number" name="max_fee" value="<?php echo $psychologist['max_fee']; ?>" required>
        <input type="time" name="office_start" value="<?php echo $psychologist['office_start']; ?>" required>
        <input type="time" name="office_end" value="<?php echo $psychologist['office_end']; ?>" required>
        <input type="text" name="contact_info" value="<?php echo $psychologist['contact_info']; ?>" required>
        <button type="submit">Update Psychologist</button>
    </form>
</body>

</html>