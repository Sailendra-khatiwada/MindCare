<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

/* ------------------------- FETCH APPOINTMENTS ------------------------- */
$stmt = $conn->prepare("
    SELECT a.*, p.username AS psychologist_name, p.profile_picture AS psychologist_image
    FROM appointments a
    JOIN psychologist p ON a.p_id = p.p_id
    WHERE a.user_id = ? 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");

if (!$stmt) {
    die("Appointments SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

/* ------------------------- FETCH MEDICATIONS ------------------------- */
$medications_stmt = $conn->prepare("
    SELECT * FROM medications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");

if (!$medications_stmt) {
    die("Medications SQL Error: " . $conn->error);
}

$medications_stmt->bind_param("i", $user_id);
$medications_stmt->execute();
$medications = $medications_stmt->get_result();

/* ------------------------- FETCH PROFILE INFO ------------------------- */
$profile_stmt = $conn->prepare("
    SELECT username, email, profile_picture, created_at 
    FROM users 
    WHERE user_id = ?
");

if (!$profile_stmt) {
    die("Profile SQL Error: " . $conn->error);
}

$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$user = $profile_result->fetch_assoc();

/* ------------------------- COUNT APPOINTMENTS ------------------------- */
$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS count 
    FROM appointments 
    WHERE user_id = ?
");

if (!$count_stmt) {
    die("Appointment Count SQL Error: " . $conn->error);
}

$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$appointment_count = $count_result->fetch_assoc()['count'];

/* ------------------------- COUNT MEDICATIONS ------------------------- */
$med_count_stmt = $conn->prepare("
    SELECT COUNT(*) AS count 
    FROM medications 
    WHERE user_id = ?
");

if (!$med_count_stmt) {
    die("Medication Count SQL Error: " . $conn->error);
}

$med_count_stmt->bind_param("i", $user_id);
$med_count_stmt->execute();
$med_count_result = $med_count_stmt->get_result();
$medication_count = $med_count_result->fetch_assoc()['count'];

/* ------------------------- COUNT COMPLETED SESSIONS ------------------------- */
$session_stmt = $conn->prepare("
    SELECT COUNT(*) AS count 
    FROM appointments 
    WHERE user_id = ? AND status = 'completed'
");

if (!$session_stmt) {
    die("Session Count SQL Error: " . $conn->error);
}

$session_stmt->bind_param("i", $user_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();
$session_count = $session_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MindCare</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/dashboard.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    
    <style>
        /* Additional inline styles for dashboard */
        .dashboard-today {
            font-size: 0.9rem;
            color: var(--medium-gray);
            margin-top: 0.5rem;
        }
        
        .welcome-time {
            font-size: 1rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-lg);
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-img" alt="Profile Picture">
            <?php else: ?>
                <img src="images/default-profile.jpg" class="profile-img" alt="Default Profile">
            <?php endif; ?>
            <h3 class="username"><?php echo htmlspecialchars($user['username']); ?></h3>
            <p class="user-role">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Appointments</span></a></li>
            <li>
                <a href="chat/messages_list_user.php" class="notify-link" id="messagesLink">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                    <span class="notification-badge" id="msgBadge" style="display:none;">0</span>
                </a>
            </li>
            <li><a href="medications.php"><i class="fas fa-pills"></i> <span>Medications</span></a></li>
            <li><a href="hospital_suggestions.php"><i class="fas fa-hospital"></i> <span>Hospitals</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user-edit"></i> <span>Profile</span></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </aside>

    <!-- Mobile menu button -->
    <button class="menu-btn" id="menuToggle"><i class="fas fa-bars"></i></button>

    <!-- Main content -->
    <main class="main-content">

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?> 👋</h2>
                    <p class="welcome-time"><?php echo date('l, F j, Y'); ?> • Good <?php 
                        $hour = date('H');
                        if ($hour < 12) echo 'Morning';
                        elseif ($hour < 17) echo 'Afternoon';
                        else echo 'Evening';
                    ?>!</p>
                    <div class="quick-actions">
                        <a href="appointments.php?action=book" class="quick-action-btn">
                            <i class="fas fa-plus"></i> Book Appointment
                        </a>
                        <a href="medications.php?action=add" class="quick-action-btn">
                            <i class="fas fa-plus"></i> Add Medication
                        </a>
                        <a href="hospital_suggestions.php" class="quick-action-btn">
                            <i class="fas fa-search"></i> Find Hospitals
                        </a>
                    </div>
                </div>
                <div class="welcome-illustration">🕊️</div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon appointments">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $appointment_count; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon medications">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $medication_count; ?></h3>
                    <p>Active Medications</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon messages">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-info">
                    <h3 id="messageCount">0</h3>
                    <p>Unread Messages</p>
                </div>
            </div>
            
        </div>

        <!-- Dashboard Grid -->
        <section class="dashboard-grid">

            <!-- Recent Appointments -->
            <div class="card fade-in">
                <h3>
                    <i class="fas fa-calendar-alt" style="margin-right: 0.5rem;"></i>
                    Recent Appointments
                </h3>
                <div class="card-body">
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <div class="appointment-item">
                                <div class="appointment-header">
                                    <strong><?php echo htmlspecialchars($appointment['psychologist_name']); ?></strong>
                                    <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="far fa-calendar"></i>
                                        <span><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                                    </div>
                                   
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="appointments.php" class="quick-action-btn" style="background: var(--primary); color: white;">
                                View All Appointments
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="far fa-calendar-times"></i>
                            <h4>No Appointments</h4>
                            <p>You don't have any appointments scheduled yet.</p>
                            <a href="appointments.php?action=book" class="quick-action-btn" style="background: var(--primary); color: white;">
                                <i class="fas fa-plus"></i> Book Your First Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Medications -->
            <div class="card fade-in">
                <h3>
                    <i class="fas fa-pills" style="margin-right: 0.5rem;"></i>
                    Current Medications
                </h3>
                <div class="card-body">
                    <?php if ($medications && $medications->num_rows > 0): ?>
                        <?php while ($medication = $medications->fetch_assoc()): ?>
                            <div class="medication-item">
                                <div class="medication-header">
                                    <strong class="medication-name"><?php echo htmlspecialchars($medication['medication_name']); ?></strong>
                                    <span class="medication-time">
                                        <i class="far fa-clock"></i> <?php echo htmlspecialchars($medication['dosage_time'] ?: 'As needed'); ?>
                                    </span>
                                </div>
                                <div class="medication-details">
                                    <div>
                                        <div class="detail-label">Dosage</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($medication['dosage']); ?></div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Frequency</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($medication['frequency']); ?></div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Start Date</div>
                                        <div class="detail-value"><?php echo date('M d, Y', strtotime($medication['start_date'])); ?></div>
                                    </div>
                                    <div>
                                        <div class="detail-label">End Date</div>
                                        <div class="detail-value"><?php echo $medication['end_date'] ? date('M d, Y', strtotime($medication['end_date'])) : 'Ongoing'; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="medications.php" class="quick-action-btn" style="background: var(--secondary); color: white;">
                                Manage Medications
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-prescription-bottle-alt"></i>
                            <h4>No Medications</h4>
                            <p>You haven't added any medications yet.</p>
                            <a href="medications.php?action=add" class="quick-action-btn" style="background: var(--secondary); color: white;">
                                <i class="fas fa-plus"></i> Add Medication
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar -->
            <div class="card calendar-card fade-in">
                <h3>
                    <i class="far fa-calendar" style="margin-right: 0.5rem;"></i>
                    Calendar
                </h3>
                <div class="calendar-header">
                    <h4 id="month-year"></h4>
                    <div class="calendar-navigation">
                        <button class="nav-btn" id="prev-month"><i class="fas fa-chevron-left"></i></button>
                        <button class="nav-btn" id="today-btn">Today</button>
                        <button class="nav-btn" id="next-month"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-days">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span>
                    <span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                <div class="calendar-dates" id="calendar-dates"></div>
            </div>

        </section>
    </main>
   
    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Calendar Script
        document.addEventListener('DOMContentLoaded', function() {
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            const daysContainer = document.getElementById("calendar-dates");
            const monthYearDisplay = document.getElementById("month-year");
            const prevMonthBtn = document.getElementById("prev-month");
            const nextMonthBtn = document.getElementById("next-month");
            const todayBtn = document.getElementById("today-btn");

            let currentDate = new Date();
            let currentYear = currentDate.getFullYear();
            let currentMonth = currentDate.getMonth();

            function renderCalendar(year = currentYear, month = currentMonth) {
                daysContainer.innerHTML = "";
                const firstDay = new Date(year, month, 1).getDay();
                const lastDate = new Date(year, month + 1, 0).getDate();
                const today = new Date();

                monthYearDisplay.textContent = `${monthNames[month]} ${year}`;

                // Add empty divs for days before the first day of the month
                for (let i = 0; i < firstDay; i++) {
                    const emptyDiv = document.createElement("div");
                    emptyDiv.classList.add("empty");
                    daysContainer.appendChild(emptyDiv);
                }

                // Add days of the month
                for (let day = 1; day <= lastDate; day++) {
                    const dateDiv = document.createElement("div");
                    dateDiv.textContent = day;

                    // Check if it's today
                    if (day === today.getDate() && 
                        month === today.getMonth() && 
                        year === today.getFullYear()) {
                        dateDiv.classList.add("current-day");
                    }

                    // Check for appointments on this day (you can customize this)
                    if (Math.random() > 0.8) { // Example: random appointment days
                        dateDiv.classList.add("appointment-day");
                    }

                    dateDiv.addEventListener('click', () => {
                        // Remove selected class from all days
                        document.querySelectorAll('.calendar-dates div').forEach(d => {
                            d.classList.remove('selected');
                        });
                        // Add selected class to clicked day
                        dateDiv.classList.add('selected');
                        
                        // Show appointments for that day
                        const selectedDate = new Date(year, month, day);
                        console.log('Selected date:', selectedDate.toDateString());
                    });

                    daysContainer.appendChild(dateDiv);
                }
            }

            prevMonthBtn.addEventListener('click', () => {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentYear, currentMonth);
            });

            nextMonthBtn.addEventListener('click', () => {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentYear, currentMonth);
            });

            todayBtn.addEventListener('click', () => {
                currentDate = new Date();
                currentYear = currentDate.getFullYear();
                currentMonth = currentDate.getMonth();
                renderCalendar(currentYear, currentMonth);
            });

            renderCalendar();
        });

        // Check for unread messages
        (function() {
            const BADGE = document.getElementById('msgBadge');
            const MESSAGE_COUNT = document.getElementById('messageCount');
            const MESSAGES_LINK = document.getElementById('messagesLink');
            const POPUP = document.getElementById('popupNotification');

            let lastUnread = 0;
            let pollingInterval = 5000; // 5 seconds

            async function checkUnread() {
                try {
                    const res = await fetch('chat/check_unread_user.php', {
                        cache: 'no-store',
                        headers: {
                            'Cache-Control': 'no-cache'
                        }
                    });
                    
                    if (!res.ok) {
                        console.error('Unread check failed:', res.status, res.statusText);
                        return;
                    }
                    
                    const data = await res.json();
                    
                    if (data && typeof data.unread !== 'undefined') {
                        const count = parseInt(data.unread, 10) || 0;

                        // Update badge in sidebar
                        if (BADGE) {
                            if (count > 0) {
                                BADGE.textContent = count > 99 ? '99+' : count;
                                BADGE.style.display = 'inline-block';
                            } else {
                                BADGE.style.display = 'none';
                            }
                        }

                        // Update stats card
                        if (MESSAGE_COUNT) {
                            MESSAGE_COUNT.textContent = count;
                        }

                        // Show notification for new messages
                        if (count > lastUnread) {
                            showNotification(count - lastUnread);
                        }

                        lastUnread = count;
                    } else {
                        console.error('Unexpected response from unread API:', data);
                    }
                } catch (err) {
                    console.error('checkUnread error:', err);
                }
            }

            function showNotification(newMessages) {
                if (newMessages > 0) {
                    // Create notification toast
                    const toast = document.createElement('div');
                    toast.className = 'notification-toast';
                    toast.innerHTML = `
                        <i class="fas fa-comment-dots"></i>
                        <div>
                            <strong>${newMessages} new message${newMessages > 1 ? 's' : ''}</strong>
                            <small>Click to view</small>
                        </div>
                        <button onclick="this.parentElement.remove()">×</button>
                    `;
                    
                    toast.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: var(--white);
                        padding: 1rem;
                        border-radius: var(--radius-lg);
                        box-shadow: var(--shadow-xl);
                        display: flex;
                        align-items: center;
                        gap: 1rem;
                        z-index: 10000;
                        animation: slideInRight 0.3s ease-out;
                        cursor: pointer;
                        border-left: 4px solid var(--primary);
                    `;
                    
                    toast.addEventListener('click', () => {
                        window.location.href = 'chat/messages_list_user.php';
                    });
                    
                    document.body.appendChild(toast);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.style.animation = 'slideOutRight 0.3s ease-out';
                            setTimeout(() => toast.remove(), 300);
                        }
                    }, 5000);
                }
            }

            // Add CSS for animations
            const style = document.createElement('style');
            style.textContent = `
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
                    color: var(--medium-gray);
                    padding: 0 0.5rem;
                }
                .notification-toast button:hover {
                    color: var(--dark);
                }
            `;
            document.head.appendChild(style);

            // Run immediately and on interval
            checkUnread();
            setInterval(checkUnread, pollingInterval);

            // Reset count when visiting messages page
            if (MESSAGES_LINK) {
                MESSAGES_LINK.addEventListener('click', () => {
                    lastUnread = 0;
                    if (BADGE) BADGE.style.display = 'none';
                    if (MESSAGE_COUNT) MESSAGE_COUNT.textContent = '0';
                });
            }

        })();

        // Add smooth animations to cards on load
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.fade-in');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Add responsive behavior for window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>

</body>

</html>