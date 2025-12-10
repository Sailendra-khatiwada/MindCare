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

// Use prepared statement for user query
$stmt = $conn->prepare("SELECT username, email, profile_picture, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows == 0) {
    echo "Error: User not found.";
    exit;
}

$user = $user_result->fetch_assoc();
$stmt->close();

// Get all appointments with psychologist info in one query
$appointments_query = "
    SELECT a.*, p.username as psychologist_name, p.specialization, p.profile_picture as psychologist_pic
    FROM appointments a
    LEFT JOIN psychologist p ON a.p_id = p.p_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
$total_appointments = $appointments_result->num_rows;

// Get stats
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM appointments 
    WHERE user_id = ?
");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Appointments | MindCare Admin</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/view_users_appointments.css">
    
</head>

<body>
    <div class="container">
        <!-- Header -->
        <header class="page-header">
            <a href="dashboard.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <a href="admin_dashboard.php#users" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Users
            </a>
        </header>

        <!-- User Profile Card -->
        <div class="user-profile-card">
            <div class="profile-header">
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'images/default-user.jpg'; ?>"
                    alt="Profile"
                    class="profile-avatar">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-calendar-plus"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    <p><i class="fas fa-calendar-check"></i> <?php echo $total_appointments; ?> total appointment<?php echo $total_appointments != 1 ? 's' : ''; ?></p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>

            <div class="stat-card approved">
                <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <h3>
                    <i class="fas fa-calendar-alt"></i>
                    Appointment History
                </h3>
                <span class="appointment-count">
                    <?php echo $total_appointments; ?> appointment<?php echo $total_appointments != 1 ? 's' : ''; ?>
                </span>
            </div>

            <?php if ($total_appointments > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Psychologist</th>
                                <th>Status</th>
                                <th>Appointment ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="date-cell">
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    </td>
                                    <td class="time-cell">
                                        <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                    </td>
                                    <td>
                                        <div class="psychologist-cell">
                                            <img src="<?php echo !empty($appointment['psychologist_pic']) ? htmlspecialchars($appointment['psychologist_pic']) : 'images/default-profile.jpg'; ?>"
                                                alt="Psychologist"
                                                class="psychologist-avatar">
                                            <div class="psychologist-info">
                                                <h4><?php echo htmlspecialchars($appointment['psychologist_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                            <?php echo $appointment['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.85rem; color: var(--medium-gray);">
                                            #<?php echo $appointment['appointment_id']; ?>
                                        </code>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 class="empty-title">No Appointments Found</h3>
                    <p>This user hasn't booked any appointments yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.style.animation = 'fadeIn 0.3s ease-out forwards';
                row.style.opacity = '0';
            });
        });
    </script>
</body>

</html>