<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

// Prepare the query to fetch appointments for the logged-in user
$stmt = $conn->prepare("
    SELECT a.*, p.username AS psychologist_name
    FROM appointments a
    JOIN psychologist p ON a.p_id = p.p_id
    WHERE a.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

// Get the result of the query
$appointments = $stmt->get_result();

// Fetch medications for the user
$medications_stmt = $conn->prepare("SELECT * FROM medications WHERE user_id = ?");
$medications_stmt->bind_param("i", $user_id);
$medications_stmt->execute();

$medications = $medications_stmt->get_result();

// Fetch profile picture for the user
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
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <div class="profile">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="images/default-profile.jpg" alt="Default Profile Picture">
                <?php endif; ?>
                <h2><?php echo $_SESSION['username']; ?></h2>
            </div>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="chat/messages_list_user.php"><i class="fas fa-comments"></i>Messages</a></li>
                <li><a href="medications.php"><i class="fas fa-pills"></i> Medications</a></li>
                <li><a href="hospital_suggestions.php"><i class="fas fa-hospital"></i>Suggested Hospitals</a></li>
                <li><a href="profile.php"><i class="fas fa-user-edit"></i> Manage Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </ul>
        </nav>
    </div>
    <i class="fas fa-bars" id="MenuBtn"></i>

    <!-- Main Dashboard Content -->
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome">
                <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
                <div class="typing"><span class="auto-input"></span></div>
                <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Appointments Section -->
            <div class="appointments" style="background-color:rgb(245, 182, 183);">
                <h3>Your Appointments</h3>
                <ul>
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <li>
                                <strong>Psychologist:</strong> <?php echo htmlspecialchars($appointment['psychologist_name']); ?><br>
                                <strong>Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?><br>
                                <strong>Time:</strong> <?php echo htmlspecialchars($appointment['appointment_time']); ?><br>
                                <strong>Status:</strong> <?php echo htmlspecialchars($appointment['status']); ?><br>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>No appointments scheduled.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Medications Section -->
            <div class="medications" style="background-color:rgb(147, 234, 196);">
                <h3>Your Medications</h3>
                <ul>
                    <?php if ($medications && $medications->num_rows > 0): ?>
                        <?php while ($medication = $medications->fetch_assoc()): ?>
                            <li>
                                <strong>Medication:</strong> <?php echo htmlspecialchars($medication['medication_name']); ?><br>
                                <strong>Dosage:</strong> <?php echo htmlspecialchars($medication['dosage']); ?><br>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li>No medications listed.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Calendar Section -->
            <div class="calendar">
                <h3>Calendar</h3>
                <div class="calendar-header">
                    <h4 id="month-year"></h4>
                </div>
                <div class="calendar-body">
                    <div class="calendar-days">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="calendar-dates" id="calendar-dates"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const daysContainer = document.getElementById("calendar-dates");
            const monthYearDisplay = document.getElementById("month-year");

            function renderCalendar() {
                const today = new Date();
                const year = today.getFullYear();
                const month = today.getMonth();

                monthYearDisplay.textContent = `${monthNames[month]} ${year}`;

                // Clear previous dates
                daysContainer.innerHTML = "";

                const firstDay = new Date(year, month, 1).getDay();

                const lastDate = new Date(year, month + 1, 0).getDate();

                // Fill in empty slots for days from the previous month
                for (let i = 0; i < firstDay; i++) {
                    const emptySlot = document.createElement("div");
                    daysContainer.appendChild(emptySlot);
                }

                // Fill in dates for the current month
                for (let day = 1; day <= lastDate; day++) {
                    const date = document.createElement("div");
                    date.textContent = day;

                    // Highlight current day
                    if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                        date.classList.add("current-day");
                    }

                    daysContainer.appendChild(date);
                }
            }

            renderCalendar();
        });

        let typed = new Typed('.auto-input', {
            strings: ['How can we support you today?', 'Connecting You to Support...', 'Ready to start your journey to wellness'],
            typeSpeed: 10,
            backSpeed: 10,
            backDelay: 1500,
            loop: true
        });

        let MenuBtn = document.getElementById('MenuBtn');
        MenuBtn.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.body.classList.toggle('mobile-nav-active');
        });

        window.addEventListener('click', function(e) {
            if (!MenuBtn.contains(e.target) && !document.querySelector('.sidebar').contains(e.target)) {
                document.querySelector('.sidebar').classList.remove('active');
                document.body.classList.remove('mobile-nav-active');
            }
        });
    </script>
</body>

</html>