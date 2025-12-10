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

// Get total unread messages from patients
$unreadQuery = $conn->query("
    SELECT COUNT(*) as unread 
    FROM messages m
    JOIN appointments a ON m.appointment_id = a.appointment_id
    WHERE a.p_id = '$psychologist_id' 
    AND m.sender_type = 'user'
    AND m.seen = 0
");
$unreadRow = $unreadQuery->fetch_assoc();
$unreadMessages = $unreadRow['unread'];

// Recent appointments with unread message count
$recentQuery = $conn->query("
    SELECT a.*, u.username, u.profile_picture as user_pic,
           (SELECT COUNT(*) FROM messages m 
            WHERE m.appointment_id = a.appointment_id 
            AND m.sender_type = 'user' 
            AND m.seen = 0) as unread_count
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.p_id = '$psychologist_id'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychologist Dashboard | MindCare</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/psychologist_dashboard.css">

    <style>
        .stat-card:nth-child(4)::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 8px;
            height: 8px;
            background-color: #ff4757;
            border-radius: 50%;
            display: <?php echo $unreadMessages > 0 ? 'block' : 'none'; ?>;
        }
    </style>

</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">MindCare Pro</div>

        <a href="psychologist_dashboard.php" class="active">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
            <?php if ($unreadMessages > 0): ?>
                <span class="badge"><?php echo $unreadMessages; ?></span>
            <?php endif; ?>
        </a>

        <a href="manage_psychologist_profile.php">
            <i class="fas fa-user-cog"></i>
            <span>Profile Settings</span>
        </a>

        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <!-- Top Bar -->
        <div class="topbar">
            <div>
                <h1>Welcome back, Dr. <?php echo htmlspecialchars($username); ?></h1>
                <p style="color: var(--medium-gray); margin-top: 0.25rem;">Here's what's happening with your practice today</p>
            </div>

            <div class="user-profile">
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($username); ?></h3>
                    <p>Psychologist</p>
                </div>
                <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'images/default-profile.jpg'; ?>"
                    alt="Profile" class="profile-image">
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $totalAppointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $approved; ?></h3>
                    <p>Confirmed Sessions</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <h3 id="unreadCount"><?php echo $unreadMessages; ?></h3>
                    <p>Unread Messages</p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Appointments Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3>Recent Appointments</h3>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentQuery->num_rows > 0): ?>
                                <?php while ($row = $recentQuery->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <img src="<?php echo !empty($row['user_pic']) ? htmlspecialchars($row['user_pic']) : 'images/default-user.jpg'; ?>"
                                                    alt="User" class="user-avatar">
                                                <span><?php echo htmlspecialchars($row['username']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form action="update_status.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" style="margin-right: 0;">
                                                        <option value="Pending" <?= strtolower($row['status']) == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Approved" <?= strtolower($row['status']) == 'approved' ? 'selected' : '' ?>>Approved</option>
                                                        <option value="Rejected" <?= strtolower($row['status']) == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                    </select>
                                                </form>

                                                <?php if (strtolower(trim($row['status'])) === 'approved'): ?>
                                                    <a href="chat/psychologist_chat.php?appointment_id=<?= $row['appointment_id']; ?>"
                                                        class="action-btn btn-chat">
                                                        <i class="fas fa-comments"></i> Chat
                                                        <?php if ($row['unread_count'] > 0): ?>
                                                            <span class="badge" id="badge-<?= $row['appointment_id']; ?>"><?= $row['unread_count']; ?></span>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-calendar-times"></i>
                                            </div>
                                            <h3>No appointments yet</h3>
                                            <p>When patients book sessions, they'll appear here.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Auto-update form submission
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                // Show loading state
                const originalText = this.parentElement.querySelector('.btn-update')?.textContent;
                const btn = this.parentElement.querySelector('.btn-update');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    btn.disabled = true;
                }

                // Submit the form
                this.form.submit();
            });
        });

        // Update time display
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            const timeElement = document.querySelector('.user-info p');
            if (timeElement) {
                timeElement.innerHTML = `Psychologist • ${timeString}`;
            }
        }

        updateTime();
        setInterval(updateTime, 60000);

        // Function to check for new unread messages
        function checkUnreadMessages() {
            fetch('check_unread_psych.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread !== undefined) {
                        const unreadCount = parseInt(data.unread);
                        const countElement = document.getElementById('unreadCount');
                        const sidebarBadge = document.querySelector('.sidebar a.active .badge');

                        // Update main counter
                        if (countElement) {
                            countElement.textContent = unreadCount;
                        }

                        // Update sidebar badge
                        if (unreadCount > 0) {
                            if (!sidebarBadge) {
                                // Create badge if it doesn't exist
                                const badge = document.createElement('span');
                                badge.className = 'badge';
                                badge.textContent = unreadCount;
                                document.querySelector('.sidebar a.active').appendChild(badge);
                            } else {
                                // Update existing badge
                                sidebarBadge.textContent = unreadCount;
                            }
                        } else if (sidebarBadge) {
                            // Remove badge if no unread messages
                            sidebarBadge.remove();
                        }

                        // Show/hide red dot on stat card
                        const statCard = document.querySelector('.stat-card:nth-child(4)');
                        if (unreadCount > 0) {
                            statCard.style.position = 'relative';
                            statCard.setAttribute('data-has-unread', 'true');
                        } else {
                            statCard.removeAttribute('data-has-unread');
                        }
                    }
                })
                .catch(error => console.error('Error checking unread messages:', error));
        }

        // Check for new messages every 10 seconds
        checkUnreadMessages();
        setInterval(checkUnreadMessages, 10000);

        // Add click handler to mark messages as read when clicking chat link
        document.querySelectorAll('.btn-chat').forEach(link => {
            link.addEventListener('click', function() {
                // Remove badge when clicking chat link
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.remove();

                    // Update unread count
                    const countElement = document.getElementById('unreadCount');
                    if (countElement) {
                        let currentCount = parseInt(countElement.textContent) || 0;
                        let badgeCount = parseInt(badge.textContent) || 1;
                        countElement.textContent = Math.max(0, currentCount - badgeCount);
                    }

                    // Update sidebar badge
                    const sidebarBadge = document.querySelector('.sidebar a.active .badge');
                    if (sidebarBadge) {
                        let sidebarCount = parseInt(sidebarBadge.textContent) || 0;
                        sidebarCount = Math.max(0, sidebarCount - badgeCount);
                        if (sidebarCount > 0) {
                            sidebarBadge.textContent = sidebarCount;
                        } else {
                            sidebarBadge.remove();
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>