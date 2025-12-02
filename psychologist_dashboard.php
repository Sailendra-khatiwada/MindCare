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

// ----- Statistics -----
$totalAppointments = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id'")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id' AND status='Pending'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id' AND status='Approved'")->fetch_assoc()['c'];
$uniquePatients = $conn->query("SELECT COUNT(DISTINCT user_id) AS c FROM appointments WHERE p_id = '$psychologist_id' AND status='Approved'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Psychologist Dashboard</title>
    <link rel="stylesheet" href="css/psychologist_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2 class="logo">P-Panel</h2>

        <a href="psychologist_dashboard.php" class="active">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>

        <a href="manage_psychologist_profile.php">
            <i class="fas fa-user-cog"></i> Profile Settings
        </a>

        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <!-- TOP BAR -->
        <div class="topbar">
            <h1>Dashboard</h1>

            <div class="top-profile">
                <?php if (!empty($profile_picture)): ?>
                    <img src="<?php echo $profile_picture; ?>" alt="Profile">
                <?php else: ?>
                    <img src="images/default-profile.jpg" alt="Default">
                <?php endif; ?>
            </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-container">

            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <div>
                    <h3><?php echo $totalAppointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div>
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-user-check"></i>
                <div>
                    <h3><?php echo $approved; ?></h3>
                    <p>Approved</p>
                </div>
            </div>

            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div>
                    <h3><?php echo $uniquePatients; ?></h3>
                    <p>Patients</p>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-section">
            <h2>Your Appointments</h2>

            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $query = $conn->query("
                SELECT a.*, u.username 
                FROM appointments a
                JOIN users u ON a.user_id = u.user_id
                WHERE a.p_id = '$psychologist_id'
                ORDER BY a.appointment_date ASC
            ");

                    while ($row = $query->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['appointment_date']; ?></td>
                            <td><?php echo $row['appointment_time']; ?></td>

                            <td>
                                <span class="status <?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-row">
                                    <form action="update_status.php" method="POST">
                                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id']; ?>">

                                        <select name="status">
                                            <option value="Pending" <?= strtolower($row['status']) == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Approved" <?= strtolower($row['status']) == 'approved' ? 'selected' : '' ?>>Approved</option>
                                            <option value="Rejected" <?= strtolower($row['status']) == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>

                                        <button type="submit" class="update-button">Update</button>
                                    </form>

                                    <?php if (strtolower(trim($row['status'])) === 'approved'): ?>
                                        <a class="chat-button" href="chat/psychologist_chat.php?appointment_id=<?= $row['appointment_id']; ?>">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>