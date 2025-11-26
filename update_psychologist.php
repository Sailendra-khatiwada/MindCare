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
    $description = trim($_POST['description']);

    if (
        empty($username) || empty($email) || empty($specialization) || empty($location) ||
        empty($education) || empty($min_fee) || empty($max_fee) ||
        empty($office_start) || empty($office_end) || empty($contact_info) || empty($description)
    ) {
        echo "<script>alert('All fields including Description are required!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address!');</script>";
    } elseif (!preg_match('/^\d{10}$/', $contact_info)) {
        echo "<script>alert('Contact number must be 10 digits!');</script>";
    } elseif ($min_fee < 0 || $max_fee < 0 || $min_fee > $max_fee) {
        echo "<script>alert('Invalid fee structure!');</script>";
    } else {

        $update_query = "
            UPDATE psychologist SET 
            username='$username',
            email='$email',
            specialization='$specialization',
            location='$location',
            education='$education',
            min_fee='$min_fee',
            max_fee='$max_fee',
            office_start='$office_start',
            office_end='$office_end',
            contact_info='$contact_info',
            description='$description'
            WHERE p_id=$p_id
        ";

        if ($conn->query($update_query)) {
            echo "<script>
                alert('Psychologist Updated Successfully!');
                window.location.href='admin_dashboard.php';
            </script>";
        } else {
            echo "Error: " . $conn->error;
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
</head>
<body>

<div class="form-container">
    <h2>Update Psychologist</h2>

    <form action="" method="POST">

        <div class="input-group">
            <label>Psychologist Name</label>
            <input type="text" name="psychologist_name" 
            value="<?php echo $psychologist['username']; ?>" required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" 
            value="<?php echo $psychologist['email']; ?>" required>
        </div>

        <div class="input-group">
            <label>Specialization</label>
            <input type="text" name="specialization" 
            value="<?php echo $psychologist['specialization']; ?>" required>
        </div>

        <div class="input-group">
            <label>Location</label>
            <input type="text" name="location" 
            value="<?php echo $psychologist['location']; ?>" required>
        </div>

        <div class="input-group">
            <label>Education</label>
            <input type="text" name="education" 
            value="<?php echo $psychologist['education']; ?>" required>
        </div>

        <div class="row">
            <div class="input-group half">
                <label>Min Fee</label>
                <input type="number" name="min_fee" 
                value="<?php echo $psychologist['min_fee']; ?>" required>
            </div>

            <div class="input-group half">
                <label>Max Fee</label>
                <input type="number" name="max_fee" 
                value="<?php echo $psychologist['max_fee']; ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="input-group half">
                <label>Office Start</label>
                <input type="time" name="office_start" 
                value="<?php echo $psychologist['office_start']; ?>" required>
            </div>

            <div class="input-group half">
                <label>Office End</label>
                <input type="time" name="office_end" 
                value="<?php echo $psychologist['office_end']; ?>" required>
            </div>
        </div>

        <div class="input-group">
            <label>Contact Number</label>
            <input type="text" name="contact_info" 
            value="<?php echo $psychologist['contact_info']; ?>" required>
        </div>

        <!-- ⭐ NEW DESCRIPTION FIELD -->
        <div class="input-group">
            <label>Description</label>
            <textarea name="description" required><?php echo $psychologist['description']; ?></textarea>
        </div>

        <button type="submit" class="btn-submit">Update</button>
    </form>
</div>

</body>
</html>
