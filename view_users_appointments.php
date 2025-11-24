<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

if (!isset($_GET['user_id'])) {
    echo "Error: User ID not provided.";
    exit;
}

$user_id = intval($_GET['user_id']);

$user_result = $conn->query("SELECT username, email FROM users WHERE user_id='$user_id'");
if ($user_result->num_rows == 0) {
    echo "Error: User not found.";
    exit;
}

$user = $user_result->fetch_assoc();

$appointments_result = $conn->query("SELECT * FROM appointments WHERE user_id='$user_id'");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Appointments for <?php echo $user['username']; ?></title>
    <link rel="stylesheet" href="view_users_appointments.css">
</head>

<body>
    <h1>Appointments for <?php echo $user['username']; ?></h1>
    <p>Email: <?php echo $user['email']; ?></p>

    <?php if ($appointments_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Appointment Date</th>
                    <th>Time</th>
                    <th>Psychologist</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                    <?php
                    // Fetch psychologist's name
                    $psychologist_id = $appointment['p_id'];
                    $psychologist_result = $conn->query("SELECT username FROM psychologist WHERE p_id='$psychologist_id'");
                    $psychologist = $psychologist_result->fetch_assoc();
                    ?>
                    <tr>
                        <td><?php echo $appointment['appointment_date']; ?></td>
                        <td><?php echo $appointment['appointment_time']; ?></td>
                        <td><?php echo $psychologist['username']; ?></td>
                        <td><?php echo $appointment['status']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found for this user.</p>
    <?php endif; ?>

    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>

</html>