<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.*, p.username AS psychologist_name
    FROM appointments a
    JOIN psychologist p ON a.p_id = p.p_id
    WHERE a.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Fetch medications
$medications_stmt = $conn->prepare("SELECT * FROM medications WHERE user_id = ?");
$medications_stmt->bind_param("i", $user_id);
$medications_stmt->execute();
$medications = $medications_stmt->get_result();

// Fetch profile picture
$profile_picture_stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$profile_picture_stmt->bind_param("i", $user_id);
$profile_picture_stmt->execute();
$profile_picture_result = $profile_picture_stmt->get_result();

if ($profile_picture_result && $profile_picture_result->num_rows > 0) {
    $user = $profile_picture_result->fetch_assoc();
} else {
    $user = ['profile_picture' => ''];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-img">
            <?php else: ?>
                <img src="images/default-profile.jpg" class="profile-img">
            <?php endif; ?>
            <h3 class="username"><?php echo $_SESSION['username']; ?></h3>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Appointments</span></a></li>
            <li>
                <a href="chat/messages_list_user.php" class="notify-link" id="messagesLink">
                    <i class="fas fa-comments"></i>
                    <span class="nav-text">Messages</span>
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
    <button class="menu-btn"><i class="fas fa-bars"></i></button>

    <!-- Main content -->
    <main class="main-content">

        <!-- Top bar -->
        <header class="topbar">
            <h2>Welcome, <?php echo $_SESSION['username']; ?> 👋</h2>
            <p class="tagline">Your personal wellness dashboard</p>
        </header>

        <!-- Dashboard Grid -->
        <section class="dashboard-grid">

            <!-- Appointments -->
            <div class="card">
                <h3>Your Appointments</h3>
                <div class="card-body">
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <div class="item">
                                <strong><?php echo htmlspecialchars($appointment['psychologist_name']); ?></strong>
                                <p>Date: <?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                                <p>Time: <?php echo htmlspecialchars($appointment['appointment_time']); ?></p>
                                <p>Status: <?php echo htmlspecialchars($appointment['status']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No appointments scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Medications -->
            <div class="card">
                <h3>Your Medications</h3>
                <div class="card-body">
                    <?php if ($medications && $medications->num_rows > 0): ?>
                        <?php while ($medication = $medications->fetch_assoc()): ?>
                            <div class="item">
                                <strong><?php echo htmlspecialchars($medication['medication_name']); ?></strong>
                                <p>Dosage: <?php echo htmlspecialchars($medication['dosage']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No medications listed.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar -->
            <div class="card calendar-card">
                <h3>Calendar</h3>
                <div class="calendar-header">
                    <h4 id="month-year"></h4>
                </div>
                <div class="calendar-body">
                    <div class="calendar-days">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span>
                        <span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="calendar-dates" id="calendar-dates"></div>
                </div>
            </div>

        </section>
    </main>
   
    <script>
        // Calendar Script
        document.addEventListener('DOMContentLoaded', function() {
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            const daysContainer = document.getElementById("calendar-dates");
            const monthYearDisplay = document.getElementById("month-year");

            function renderCalendar() {
                const today = new Date();
                const year = today.getFullYear();
                const month = today.getMonth();
                monthYearDisplay.textContent = `${monthNames[month]} ${year}`;

                daysContainer.innerHTML = "";
                const firstDay = new Date(year, month, 1).getDay();
                const lastDate = new Date(year, month + 1, 0).getDate();

                for (let i = 0; i < firstDay; i++) {
                    daysContainer.appendChild(document.createElement("div"));
                }

                for (let day = 1; day <= lastDate; day++) {
                    const date = document.createElement("div");
                    date.textContent = day;

                    if (day === today.getDate()) {
                        date.classList.add("current-day");
                    }

                    daysContainer.appendChild(date);
                }
            }
            renderCalendar();
        });

        // Mobile Menu Toggle
        const menuBtn = document.querySelector(".menu-btn");
        const sidebar = document.querySelector(".sidebar");

        menuBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
        });


        (function() {
            const BADGE = document.getElementById('msgBadge');
            const POPUP = document.getElementById('popupNotification');
            const POPUP_TITLE = document.getElementById('popupTitle');
            const POPUP_TEXT = document.getElementById('popupText');
            const MESSAGES_LINK = document.getElementById('messagesLink');

            let lastUnread = 0;
            let pollingInterval = 4000; // 5s


            async function checkUnread() {
                try {
                    const res = await fetch('chat/check_unread_user.php', {
                        cache: 'no-store'
                    });
                    if (!res.ok) {
                        console.error('Unread check failed:', res.status, res.statusText);
                        return;
                    }
                    const data = await res.json();
                    if (data && typeof data.unread !== 'undefined') {
                        const count = parseInt(data.unread, 10) || 0;

                        // update badge
                        if (BADGE) {
                            if (count > 0) {
                                BADGE.textContent = count > 99 ? '99+' : count;
                                BADGE.style.display = 'inline-block';
                            } else {
                                BADGE.style.display = 'none';
                            }
                        }

                        // show popup when new messages arrived (count increased)
                        if (count > lastUnread) {
                            // optionally show message content or number
                            const diff = count - lastUnread;
                        }

                        lastUnread = count;
                    } else {
                        console.error('Unexpected response from unread API:', data);
                    }
                } catch (err) {
                    console.error('checkUnread error:', err);
                }
            }

            // run immediately and on interval
            checkUnread();
            setInterval(checkUnread, pollingInterval);

            // optional: clicking popup or badge navigates to messages
            if (POPUP) {
                POPUP.addEventListener('click', () => {
                    window.location.href = 'chat/messages_list_user.php';
                });
            }
            if (MESSAGES_LINK) {
                MESSAGES_LINK.addEventListener('click', () => {
                    // when user opens Messages, we can reset lastUnread to 0 locally
                    lastUnread = 0;
                    if (BADGE) BADGE.style.display = 'none';
                });
            }

        })();
    </script>

</body>

</html>