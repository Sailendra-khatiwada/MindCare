<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $medication_name = $_POST['medication_name'];
    $dosage = $_POST['dosage'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("INSERT INTO medications (user_id, medication_name, dosage, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $medication_name, $dosage, $start_date, $end_date);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Medication added successfully!";
        header('Location: medications.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Medications</title>
    <link rel="stylesheet" href="medications.css">
</head>

<body>
    <div class="form-container">
        <h2>Add Medication</h2>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="medication_name" placeholder="Medication Name" required><br>
            <input type="text" name="dosage" placeholder="Dosage" required><br>
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" required><br>
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date"><br>
            <button type="submit">Save</button>
            <a href="dashboard.php"><button type="button">Back Home</button></a>

        </form>
    </div>
</body>

</html>