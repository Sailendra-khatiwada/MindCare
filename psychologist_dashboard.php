<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
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

$totalAppointments = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id'")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id' AND status='Pending'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) AS c FROM appointments WHERE p_id = '$psychologist_id' AND status='Approved'")->fetch_assoc()['c'];

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/psychologist_dashboard.css">

    <style>
        .notification-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .stat-card {
            position: relative;
        }
        
        .stat-card.has-unread::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 10px;
            height: 10px;
            background-color: #ff4757;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
            cursor: pointer;
            border-left: 4px solid #4a7b9d;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .notification-toast button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
            padding: 0 0.5rem;
        }
        
        .notification-toast button:hover {
            color: #2c3e50;
        }
        
        .new-message-row {
            animation: highlight 1s ease-out;
        }
        
        @keyframes highlight {
            0% { background-color: rgba(74, 123, 157, 0.2); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">MindCare Pro</div>

        <a href="psychologist_dashboard.php" class="active">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
            </span>
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

    <div class="main">
        <div class="topbar">
            <div>
                <h1>Welcome back, Dr. <?php echo htmlspecialchars($username); ?></h1>
                <p style="color: #6c757d; margin-top: 0.25rem;">Here's what's happening with your practice today</p>
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

            <div class="stat-card" id="unreadStatCard">
                <div class="stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <h3 id="unreadCount"><?php echo $unreadMessages; ?></h3>
                    <p>Unread Messages</p>
                </div>
            </div>
        </div>

        <div class="content-grid">
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
                                    <tr id="appointment-<?= $row['appointment_id']; ?>">
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
                                                        class="action-btn btn-chat" data-appointment="<?= $row['appointment_id']; ?>">
                                                        <i class="fas fa-comments"></i> Chat
                                                        <span class="notification-badge chat-badge" id="badge-<?= $row['appointment_id']; ?>" 
                                                              style="display: <?= $row['unread_count'] > 0 ? 'inline-block' : 'none'; ?>">
                                                            <?= $row['unread_count']; ?>
                                                        </span>
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
    (function() {
        const MESSAGE_COUNT = document.getElementById('unreadCount');
        const STAT_CARD = document.getElementById('unreadStatCard');
        let lastUnread = <?php echo $unreadMessages; ?>;
        let pollingInterval = 3000; // Check every 3 seconds
        
        // Store initial unread counts for each conversation
        let previousUnreadCounts = {};
        document.querySelectorAll('.chat-badge').forEach(badge => {
            const appointmentId = badge.id.replace('badge-', '');
            previousUnreadCounts[appointmentId] = parseInt(badge.textContent) || 0;
        });

        async function checkUnread() {
            try {
                const res = await fetch('check_unread_psych.php', {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!res.ok) {
                    console.error('Unread check failed:', res.status);
                    return;
                }
                
                const data = await res.json();
                
                if (data && typeof data.unread !== 'undefined') {
                    const count = parseInt(data.unread, 10) || 0;
                    
                    // Update stat card count
                    if (MESSAGE_COUNT) {
                        MESSAGE_COUNT.textContent = count;
                    }
                    
                    // Update stat card indicator
                    if (STAT_CARD) {
                        if (count > 0) {
                            STAT_CARD.classList.add('has-unread');
                        } else {
                            STAT_CARD.classList.remove('has-unread');
                        }
                    }
                    
                    // Show notification for new messages
                    if (count > lastUnread) {
                        showNotification(count - lastUnread);
                    }
                    
                    // If new messages arrived, reload the page data via AJAX
                    if (count !== lastUnread) {
                        refreshAppointmentsTable();
                    }
                    
                    lastUnread = count;
                }
            } catch (err) {
                console.error('checkUnread error:', err);
            }
        }

        async function refreshAppointmentsTable() {
            try {
                const res = await fetch('get_recent_appointments.php', {
                    cache: 'no-store'
                });
                
                if (!res.ok) return;
                
                const html = await res.text();
                const tbody = document.querySelector('table tbody');
                if (tbody) {
                    tbody.innerHTML = html;
                    
                    // Re-attach event listeners to new chat buttons
                    attachChatButtonListeners();
                    
                    // Re-store unread counts
                    document.querySelectorAll('.chat-badge').forEach(badge => {
                        const appointmentId = badge.id.replace('badge-', '');
                        previousUnreadCounts[appointmentId] = parseInt(badge.textContent) || 0;
                    });
                }
            } catch (err) {
                console.error('refreshAppointmentsTable error:', err);
            }
        }

        function showNotification(newMessages) {
            if (newMessages > 0) {
                const toast = document.createElement('div');
                toast.className = 'notification-toast';
                toast.innerHTML = `
                    <i class="fas fa-comment-dots"></i>
                    <div>
                        <strong>${newMessages} new message${newMessages > 1 ? 's' : ''}</strong>
                        <small>Click to refresh</small>
                    </div>
                    <button onclick="this.parentElement.remove()">×</button>
                `;
                
                toast.addEventListener('click', () => {
                    location.reload();
                });
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.style.animation = 'slideOutRight 0.3s ease-out';
                        setTimeout(() => toast.remove(), 300);
                    }
                }, 5000);
            }
        }

        function attachChatButtonListeners() {
            document.querySelectorAll('.btn-chat').forEach(link => {
                link.addEventListener('click', function() {
                    const badge = this.querySelector('.chat-badge');
                    if (badge) {
                        const appointmentId = this.dataset.appointment;
                        const badgeCount = parseInt(badge.textContent) || 0;
                        
                        lastUnread = Math.max(0, lastUnread - badgeCount);
                        previousUnreadCounts[appointmentId] = 0;
                        
                        if (MESSAGE_COUNT) {
                            MESSAGE_COUNT.textContent = lastUnread;
                        }
                        
                        if (lastUnread === 0 && STAT_CARD) {
                            STAT_CARD.classList.remove('has-unread');
                        }
                        
                        badge.style.display = 'none';
                    }
                });
            });
        }

        // Start polling
        checkUnread();
        setInterval(checkUnread, pollingInterval);
        
        // Initial attachment of listeners
        attachChatButtonListeners();
        
        // Add styles for notification
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .notification-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 1rem;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 1rem;
                z-index: 10000;
                animation: slideInRight 0.3s ease-out;
                cursor: pointer;
                border-left: 4px solid #4a7b9d;
            }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .notification-toast button {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6c757d;
                padding: 0 0.5rem;
            }
            .stat-card.has-unread::after {
                content: '';
                position: absolute;
                top: 10px;
                right: 10px;
                width: 10px;
                height: 10px;
                background-color: #ff4757;
                border-radius: 50%;
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(1.2); }
            }
        `;
        document.head.appendChild(style);
    })();
</script>
</body>
</html>