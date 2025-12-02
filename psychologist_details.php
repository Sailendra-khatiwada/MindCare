<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'db_connect.php';

$appointmentSuccess = false;
$errorMessage = '';

if (!isset($_GET['id'])) {
    die("Invalid psychologist ID.");
}

$psychologist_id = intval($_GET['id']);

// Fetch Psychologist Info
$stmt = $conn->prepare("
    SELECT username, profile_picture, specialization, location, education,
           min_fee, max_fee, office_start, office_end, contact_info, description, email, AreaOfExperties
    FROM psychologist
    WHERE p_id = ?
");
$stmt->bind_param("i", $psychologist_id);
$stmt->execute();
$result = $stmt->get_result();
$psych = $result->fetch_assoc();
$stmt->close();

/* ------------------- HANDLE BOOKING FORM ------------------- */

$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Prevent past dates
    if ($appointment_date < date('Y-m-d')) {
        $errorMessage = "You cannot book an appointment in the past.";
    } else {

        // Get hours
        $stmt = $conn->prepare("SELECT office_start, office_end FROM psychologist WHERE p_id = ?");
        $stmt->bind_param("i", $psychologist_id);
        $stmt->execute();
        $stmt->bind_result($office_start, $office_end);
        $stmt->fetch();
        $stmt->close();

        $t = strtotime($appointment_time);
        if ($t < strtotime($office_start) || $t > strtotime($office_end)) {
            $errorMessage = "Appointment is outside office hours.";
        } else {

            // Check conflicts (30 min rule)
            $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE p_id=? AND appointment_date=?");
            $stmt->bind_param("is", $psychologist_id, $appointment_date);
            $stmt->execute();
            $stmt->bind_result($existing);
            $conflict = false;
            while ($stmt->fetch()) {
                if (abs(strtotime($existing) - $t) < 1800) $conflict = true;
            }
            $stmt->close();

            if ($conflict) {
                $errorMessage = "This time overlaps another appointment.";
            } else {

                // Max 5 daily
                $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE p_id=? AND appointment_date=?");
                $stmt->bind_param("is", $psychologist_id, $appointment_date);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count >= 5) {
                    $errorMessage = "This psychologist is fully booked for that day.";
                } else {

                    // Insert Appointment
                    $stmt = $conn->prepare("
                        INSERT INTO appointments (user_id, p_id, appointment_date, appointment_time)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("iiss", $_SESSION['user_id'], $psychologist_id, $appointment_date, $appointment_time);
                    if ($stmt->execute()) $appointmentSuccess = true;
                    else $errorMessage = "Database error: " . $stmt->error;
                    $stmt->close();
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Psychologist Details</title>
    <link rel="stylesheet" href="">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --muted: #6b7280;
            --accent: #4A90E2;
            --accent-600: #357abd;
            --success: #16a34a;
            --danger: #dc2626;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-1: 0 6px 22px rgba(16, 24, 40, 0.08);
            --shadow-2: 0 4px 14px rgba(16, 24, 40, 0.06);
        }

        /* Reset + base */
        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(180deg, #f7f9fc 0%, var(--bg) 100%);
            color: #111827;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding: 32px;
        }

        /* Container (main white card) */
        .details-container {
            max-width: 1100px;
            margin: 0 auto;
            background: var(--card);
            border-radius: calc(var(--radius-lg));
            padding: 28px;
            box-shadow: var(--shadow-1);
            border: 1px solid rgba(15, 23, 42, 0.03);
        }

        /* Header area: photo + main info */
        .details-header {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 22px;
            align-items: center;
        }

        /* Profile photo */
        .details-photo {
            width: 160px;
            height: 160px;
            border-radius: 14px;
            object-fit: cover;
            box-shadow: var(--shadow-2);
            border: 1px solid rgba(15, 23, 42, 0.04);
        }

        /* Info block */
        .details-info h2 {
            margin: 0 0 8px 0;
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .details-info .verified {
            color: var(--accent);
            font-size: 1rem;
            background: rgba(74, 144, 226, 0.08);
            padding: 4px 8px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* specialization and metadata */
        .details-info .specialization {
            margin: 6px 0 12px;
            color: var(--muted);
            font-weight: 500;
        }

        .meta-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 18px;
        }

        .meta-item {
            display: flex;
            gap: 8px;
            align-items: center;
            background: #fbfdff;
            border-radius: 10px;
            padding: 8px 12px;
            border: 1px solid rgba(15, 23, 42, 0.03);
            font-size: 0.95rem;
            color: #374151;
        }

        /* larger description area */
        .details-bio {
            margin-top: 22px;
            line-height: 1.6;
            color: #374151;
        }

        .details-bio h3 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Layout for details + booking side-by-side */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 22px;
            margin-top: 22px;
        }

        /* Appointment box (right column) */
        .appointment-form {
            background: linear-gradient(180deg, #ffffff, #fbfdff);
            padding: 18px;
            border-radius: var(--radius-md);
            border: 1px solid rgba(15, 23, 42, 0.04);
            box-shadow: var(--shadow-2);
        }

        .appointment-form h3 {
            margin: 0 0 10px 0;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .alert.error {
            background: rgba(220, 38, 38, 0.08);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.12);
        }

        .alert.success {
            background: rgba(16, 185, 129, 0.08);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.12);
        }

        /* Form elements */
        .appointment-form label {
            display: block;
            font-weight: 600;
            margin-top: 10px;
            font-size: 0.95rem;
            color: #111827;
        }

        .input-row {
            margin-top: 8px;
        }

        .appointment-form input[type="date"],
        .appointment-form input[type="time"],
        .appointment-form select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 0.98rem;
            background: #fff;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .appointment-form input:focus,
        .appointment-form select:focus {
            border-color: var(--accent);
            box-shadow: 0 6px 18px rgba(74, 144, 226, 0.08);
        }

        /* submit */
        .appointment-form button {
            margin-top: 14px;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            background: linear-gradient(180deg, var(--accent), var(--accent-600));
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .appointment-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(53, 122, 200, 0.18);
        }

        /* Back link */
        .view-more-btn,
        .back-btn {
            display: inline-block;
            margin-top: 18px;
            text-decoration: none;
            background: transparent;
            color: var(--accent);
            font-weight: 600;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px dashed rgba(74, 144, 226, 0.18);
        }

        .view-more-btn:hover,
        .back-btn:hover {
            background: rgba(74, 144, 226, 0.06);
        }

        /* small utility text */
        .small {
            font-size: 0.9rem;
            color: var(--muted);
            margin-top: 8px;
        }

        /* Responsive rules */
        @media (max-width: 980px) {
            .details-header {
                grid-template-columns: 120px 1fr;
                gap: 16px;
            }

            .details-photo {
                width: 120px;
                height: 120px;
                border-radius: 12px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .appointment-form {
                order: 2;
            }
        }

        @media (max-width: 560px) {
            body {
                padding: 20px;
            }

            .details-container {
                padding: 18px;
                border-radius: 12px;
            }

            .details-header {
                grid-template-columns: 96px 1fr;
                gap: 12px;
            }

            .details-photo {
                width: 96px;
                height: 96px;
            }

            .meta-list {
                gap: 10px 12px;
            }
        }

        /* small print for office hours & fees */
        .meta-note {
            display: inline-block;
            font-size: 0.92rem;
            color: var(--muted);
            margin-top: 8px;
        }

        /* subtle divider */
        .hr {
            height: 1px;
            background: linear-gradient(90deg, rgba(15, 23, 42, 0.02), rgba(15, 23, 42, 0.04));
            margin: 18px 0;
            border-radius: 2px;
        }
    </style>
</head>

<body>

    <div class="details-container">

        <!-- Profile Section -->
        <div class="details-header">
            <img src="<?php echo $psych['profile_picture'] ?: 'images/default-profile.jpg'; ?>" class="details-photo">

            <div class="details-info">
                <h2><?php echo $psych['username']; ?> <span class="verified">&#10004;</span></h2>
                <p class="specialization"><?php echo $psych['specialization']; ?></p>
                <p><strong>Location:</strong> <?php echo $psych['location']; ?></p>
                <p><strong>Education:</strong> <?php echo $psych['education']; ?></p>
                <p><strong>Office Hours:</strong>
                    <?php echo date('g:i A', strtotime($psych['office_start']))
                        . " - " .
                        date('g:i A', strtotime($psych['office_end'])); ?>
                </p>
                <p><strong>Fees:</strong> Rs. <?php echo $psych['min_fee']; ?> - <?php echo $psych['max_fee']; ?></p>
                <p><strong>Contact:</strong> <?php echo $psych['contact_info']; ?></p>
                <p><strong>Email:</strong> <?php echo $psych['email']; ?></p>

            </div>
        </div>

        <!-- Description -->
        <div class="details-bio">
            <h3>About</h3>
            <p><?php echo nl2br($psych['description']); ?></p>
            <div>
                <h3>Area of Experties</h3>
                <p><?php echo nl2br($psych['AreaOfExperties']); ?></p>           
            </div>
        </div>

        <!-- Appointment Form -->
        <div class="appointment-form">
            <h3>Book Appointment</h3>

            <?php if ($errorMessage): ?>
                <p style="color:red;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>

            <?php if ($appointmentSuccess): ?>
                <script>
                    alert("Appointment Booked Successfully!");
                </script>
            <?php endif; ?>

            <form method="POST">
                <label>Date:</label>
                <input type="date" name="appointment_date" required>

                <label>Time:</label>
                <input type="time" name="appointment_time" required>

                <button type="submit">Confirm Appointment</button>
            </form>
        </div>

        <br>
        <a href="appointments.php" class="view-more-btn">← Back to List</a>

    </div>

</body>

</html>