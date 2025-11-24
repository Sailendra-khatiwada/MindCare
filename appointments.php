<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'db_connect.php';

$appointmentSuccess = false;
$errorMessage = '';

// Preserve form data in case of validation errors
$psychologist_id = isset($_POST['p_id']) ? $_POST['p_id'] : '';
$appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
$appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $psychologist_id = $_POST['p_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // Check if the appointment date is in the past
    $current_date = date('Y-m-d');
    if ($appointment_date < $current_date) {
        $errorMessage = "You cannot book an appointment in the past.";
    } else {
        // Fetch psychologist's office hours
        $stmt = $conn->prepare("SELECT office_start, office_end FROM psychologist WHERE p_id = ?");
        $stmt->bind_param("i", $psychologist_id);
        $stmt->execute();
        $stmt->bind_result($office_start, $office_end);
        $stmt->fetch();
        $stmt->close();

        // Check if appointment is within office hours
        $appointment_time_str = strtotime($appointment_time);
        $office_start_str = strtotime($office_start);
        $office_end_str = strtotime($office_end);

        if ($appointment_time_str < $office_start_str || $appointment_time_str > $office_end_str) {
            $errorMessage = "The appointment time is outside the psychologist's office hours.";
        } else {
            // Check for conflicting appointments (within 30 min)
            $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE p_id = ? AND appointment_date = ?");
            $stmt->bind_param("is", $psychologist_id, $appointment_date);
            $stmt->execute();
            $stmt->bind_result($existing_time);

            $conflict = false;
            while ($stmt->fetch()) {
                $existing_time_str = strtotime($existing_time);
                if (abs($appointment_time_str - $existing_time_str) < 1800) { // 30 min gap
                    $conflict = true;
                    break;
                }
            }
            $stmt->close();

            if ($conflict) {
                $errorMessage = "The appointment time must be at least 30 minutes apart from any existing appointments.";
            } else {
                // Check if the psychologist has available slots (max 5 per day)
                $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE p_id = ? AND appointment_date = ?");
                $stmt->bind_param("is", $psychologist_id, $appointment_date);
                $stmt->execute();
                $stmt->bind_result($appointment_count);
                $stmt->fetch();
                $stmt->close();

                if ($appointment_count >= 5) {
                    $errorMessage = "The psychologist has already reached the maximum number of appointments for the day.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO appointments (user_id, p_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $_SESSION['user_id'], $psychologist_id, $appointment_date, $appointment_time);

                    if ($stmt->execute()) {
                        $appointmentSuccess = true;
                        $psychologist_id = '';
                        $appointment_date = '';
                        $appointment_time = '';
                    } else {
                        $errorMessage = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$psychologists = $conn->query("
    SELECT p_id, username, profile_picture, specialization, location, education, min_fee, max_fee, office_start, office_end, contact_info
    FROM psychologist
");

if (!$psychologists) {
    $errorMessage = "Error fetching psychologists: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="appointment.css">
    <script>
        // Filter by Location
        function filterLocation() {
            let input = document.getElementById('search-location').value.toLowerCase();
            let cards = document.getElementsByClassName('card');

            for (let card of cards) {
                let location = card.querySelector('.card-details p').innerText.toLowerCase();
                card.style.display = location.includes(input) ? 'block' : 'none';
            }
        }

        // Filter by Specialization
        function filterSpecialization(specialization) {
            let cards = document.getElementsByClassName('card');
            for (let card of cards) {
                let cardSpecialization = card.querySelector('.specialization').innerText.toLowerCase();
                card.style.display = specialization === 'All' || cardSpecialization.includes(specialization.toLowerCase()) ? 'block' : 'none';
            }
            // Update active class
            let filterOptions = document.querySelectorAll('.filter-options a');
            filterOptions.forEach(option => option.classList.remove('active'));
            document.getElementById(`filter-${specialization.replace(/\s+/g, '-')}`).classList.add('active');
        }

        function clearForm() {
            document.querySelector(".appointment-form").reset();
        }
    </script>
</head>

<body>

    <h2>Book an Appointment</h2>

    <!-- Filters -->
    <div class="filter-options">
        <a href="#" id="filter-All" onclick="filterSpecialization('All')">All</a>
        <a href="#" id="filter-Psychologist" onclick="filterSpecialization('Psychologist')">Psychologist</a>
        <a href="#" id="filter-Psychiatrist" onclick="filterSpecialization('Psychiatrist')">Psychiatrist</a>
        <a href="#" id="filter-Clinical-Psychologist" onclick="filterSpecialization('Clinical Psychologist')">Clinical Psychologist</a>
        <a href="#" id="filter-Psychotherapist" onclick="filterSpecialization('Psychotherapist')">Psychotherapist</a>
        <a href="#" id="filter-Counseling-Psychologist" onclick="filterSpecialization('Counseling Psychologist')">Counseling Psychologist</a>
        <a href="#" id="filter-Therapist" onclick="filterSpecialization('Therapist')">Therapist</a>
    </div>

    <!-- Location Search -->
    <input type="text" id="search-location" placeholder="Search by Location" onkeyup="filterLocation()">

    <!-- Display error message if any -->
    <?php if (!empty($errorMessage)): ?>
        <script>
            alert("<?php echo $errorMessage; ?>");
        </script>
    <?php endif; ?>

    <!-- Success Message & Clear Form -->
    <?php if ($appointmentSuccess): ?>
        <script>
            alert("Your appointment has been successfully booked.");
            clearForm(); 
        </script>
    <?php endif; ?>

    <!-- Psychologist List -->
    <div class="psychologist-cards">
        <?php while ($row = $psychologists->fetch_assoc()): ?>
            <div class="card">
                <div class="card-header">
                    <img src="<?php echo !empty($row['profile_picture']) ? $row['profile_picture'] : 'images/default-profile.jpg'; ?>"
                        alt="Psychologist Photo" class="profile-pic">
                    <div class="card-title">
                        <h3><?php echo htmlspecialchars($row['username']); ?> <span class="verified">&#10004;</span></h3>
                        <p class="specialization"><?php echo htmlspecialchars($row['specialization']); ?></p>
                    </div>
                </div>
                <div class="card-details">
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                    <p><strong>Education:</strong> <?php echo htmlspecialchars($row['education']); ?></p>
                    <p><strong>Office Hours:</strong>
                        <?php echo htmlspecialchars(date('g:i A', strtotime($row['office_start']))) . " - " . htmlspecialchars(date('g:i A', strtotime($row['office_end']))); ?>
                    </p>
                    <p><strong>Fees:</strong> Rs. <?php echo htmlspecialchars($row['min_fee']); ?> - Rs. <?php echo htmlspecialchars($row['max_fee']); ?></p>
                    <p><strong>Contact: </strong><?php echo htmlspecialchars($row['contact_info']); ?></p>
                </div>

                <!-- Appointment Form -->
                <form method="POST" class="appointment-form">
                    <input type="hidden" name="p_id" value="<?php echo $row['p_id']; ?>">

                    <label>Date:</label>
                    <input type="date" name="appointment_date" value="<?php echo htmlspecialchars($appointment_date); ?>" required>

                    <label>Time:</label>
                    <input type="time" name="appointment_time" value="<?php echo htmlspecialchars($appointment_time); ?>" required>

                    <button type="submit">Book Appointment</button>
                </form>

            </div>
        <?php endwhile; ?>
    </div>

</body>

</html>