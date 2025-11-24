<?php
session_start();
include 'db_connect.php';

// Ensure the psychologist is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];

// Fetch psychologist info
$stmt = $conn->prepare("SELECT p_id, profile_picture FROM psychologist WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($psychologist_id, $profile_picture);
$stmt->fetch();
$stmt->close();

if (!$psychologist_id) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Psychologist Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #6a82fb;
            padding: 10px 30px;
            color: #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 {
            font-size: 1.8rem;
            margin: 0;
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            margin-left: 20px;
            display: flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links a i {
            margin-right: 6px;
        }

        .profile-picture img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-left: 15px;
            object-fit: cover;
        }

        /* Dashboard container */
        .dashboard-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        h2 {
            color: #333;
            margin-bottom: 15px;
        }

        /* Appointment table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        table th,
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background: #6a82fb;
            color: #fff;
        }

        .update-button,
        .message-button {
            padding: 5px 10px;
            border: none;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s;
        }

        .update-button {
            background: #1abc9c;
        }

        .update-button:hover {
            background: #17a085;
        }

        .message-button {
            background: #6a82fb;
            text-decoration: none;
            display: inline-block;
        }

        .message-button:hover {
            background: #5f72ff;
        }

        select {
            border-radius: 5px;
            padding: 5px;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <h1>Psychologist Dashboard</h1>
        <div class="nav-links">
            <a href="manage_psychologist_profile.php"><i class="fas fa-user-cog"></i> Manage Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <div class="profile-picture">
                <?php if (!empty($profile_picture)): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="images/default-profile.jpg" alt="Default Profile Picture">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-container">

        <!-- Appointments -->
        <div class="appointments">
            <h2>Your Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>User's Name</th>
                        <th>Appointment Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $appointment_query = $conn->query("
                        SELECT a.*, u.username 
                        FROM appointments a
                        JOIN users u ON a.user_id = u.user_id
                        WHERE a.p_id = '$psychologist_id'
                        ORDER BY a.appointment_date DESC
                    ");

                    while ($appointment = $appointment_query->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['username']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                            <td>
                                <form action="update_status.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                    <select name="status">
                                        <option value="Pending" <?php echo strtolower($appointment['status']) == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Approved" <?php echo strtolower($appointment['status']) == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Rejected" <?php echo strtolower($appointment['status']) == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" class="update-button">Update</button>
                                </form>

                                <?php if (strtolower(trim($appointment['status'])) === 'approved'): ?>
                                   <a href="chat/psychologist_chat.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary"><i class="fas fa-comments"></i> Chat</a>
                                  
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>