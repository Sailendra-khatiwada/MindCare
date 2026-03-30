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

$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($appointment_date < date('Y-m-d')) {
        $errorMessage = "You cannot book an appointment in the past.";
    } else {
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
                $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE p_id=? AND appointment_date=?");
                $stmt->bind_param("is", $psychologist_id, $appointment_date);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count >= 5) {
                    $errorMessage = "This psychologist is fully booked for that day.";
                } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychologist Details | MindCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    
    <style>
        :root {
            --primary: #4a7b9d;
            --primary-light: #7ba6c1;
            --primary-dark: #2c5a78;
            --secondary: #6a9c89;
            --accent: #e8a87c;
            --light: #f8f9fa;
            --light-gray: #e9ecef;
            --medium-gray: #adb5bd;
            --dark: #2d3748;
            --white: #ffffff;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;

            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-card: linear-gradient(135deg, var(--white) 0%, #fcfdfe 100%);
            --gradient-header: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.12);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 50%;
            
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fb 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: var(--space-md);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-md);
            border-bottom: 2px solid var(--light-gray);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            text-decoration: none;
            color: var(--primary-dark);
        }

        .brand-mark {
            font-size: 2rem;
        }

        .brand-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            background: var(--white);
            border: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: var(--light);
            border-color: var(--primary-light);
        }

        .details-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: var(--space-lg);
        }

        @media (max-width: 1024px) {
            .details-layout {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }
        }

        .profile-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--light-gray);
        }

        .profile-header {
            background: var(--gradient-header);
            padding: var(--space-lg);
            position: relative;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .profile-image {
            width: 140px;
            height: 140px;
            border-radius: var(--radius-full);
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: var(--white);
            padding: 4px;
            flex-shrink: 0;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            color: var(--white);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: var(--space-xs);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .verified-badge {
            background: var(--white);
            color: var(--primary);
            width: 24px;
            height: 24px;
            border-radius: var(--radius-full);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .specialization-tag {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.9rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .profile-details {
            padding: var(--space-lg);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .detail-item {
            background: var(--light);
            padding: var(--space-md);
            border-radius: var(--radius-lg);
            border: 1px solid var(--light-gray);
        }

        .detail-label {
            font-size: 0.85rem;
            color: var(--medium-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark);
            line-height: 1.5;
        }

        .detail-value.highlight {
            color: var(--primary);
            font-weight: 600;
        }

        .about-section {
            background: var(--light);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            margin-top: var(--space-lg);
            border: 1px solid var(--light-gray);
        }

        .about-section h3 {
            font-size: 1.25rem;
            color: var(--primary-dark);
            margin-bottom: var(--space-sm);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .about-content {
            color: var(--dark);
            line-height: 1.8;
        }

        .appointment-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--light-gray);
            position: sticky;
            top: var(--space-md);
        }

        .appointment-header {
            margin-bottom: var(--space-lg);
        }

        .appointment-header h3 {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: var(--space-xs);
        }

        .appointment-header p {
            color: var(--medium-gray);
            font-size: 0.95rem;
        }

        .alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-md);
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: var(--space-sm);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .appointment-form {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-md);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 123, 157, 0.1);
        }

        .submit-btn {
            background: var(--gradient-primary);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: var(--space-sm);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .info-box {
            background: rgba(74, 123, 157, 0.05);
            border: 1px solid rgba(74, 123, 157, 0.1);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-top: var(--space-lg);
        }

        .info-box h4 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box ul {
            list-style: none;
            padding-left: 1.5rem;
        }

        .info-box li {
            margin-bottom: 0.5rem;
            color: var(--medium-gray);
            position: relative;
        }

        .info-box li:before {
            content: "•";
            color: var(--primary);
            position: absolute;
            left: -1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-sm);
            margin-top: var(--space-md);
        }

        .stat-item {
            text-align: center;
            padding: var(--space-md);
            background: var(--light);
            border-radius: var(--radius-md);
            border: 1px solid var(--light-gray);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--medium-gray);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            body {
                padding: var(--space-sm);
            }
            
            .page-header {
                flex-direction: column;
                gap: var(--space-md);
                align-items: flex-start;
            }
            
            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-image {
                width: 120px;
                height: 120px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .appointment-card {
                position: static;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-card,
        .appointment-card {
            animation: fadeIn 0.5s ease-out;
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="page-header">
            <a href="dashboard.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <a href="appointments.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Psychologists
            </a>
        </header>
        <div class="details-layout">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-header-content">
                        <img src="<?php echo $psych['profile_picture'] ?: 'images/default-profile.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($psych['username']); ?>" 
                             class="profile-image">
                        <div class="profile-info">
                            <h1 class="profile-name">
                                <?php echo htmlspecialchars($psych['username']); ?>
                                <span class="verified-badge" title="Verified Professional">
                                    <i class="fas fa-check"></i>
                                </span>
                            </h1>
                            <span class="specialization-tag">
                                <?php echo htmlspecialchars($psych['specialization']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="profile-details">
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value"><?php echo htmlspecialchars($psych['location']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Education</div>
                            <div class="detail-value"><?php echo htmlspecialchars($psych['education']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Office Hours</div>
                            <div class="detail-value highlight">
                                <?php echo date('g:i A', strtotime($psych['office_start'])) . " - " . date('g:i A', strtotime($psych['office_end'])); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Session Fee</div>
                            <div class="detail-value highlight">
                                Rs. <?php echo number_format($psych['min_fee']); ?> - <?php echo number_format($psych['max_fee']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Contact</div>
                            <div class="detail-value"><?php echo htmlspecialchars($psych['contact_info']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($psych['email']); ?></div>
                        </div>
                    </div>

                    <div class="about-section">
                        <h3><i class="fas fa-user"></i> About</h3>
                        <div class="about-content">
                            <?php echo nl2br(htmlspecialchars($psych['description'])); ?>
                        </div>
                    </div>

                    <div class="about-section">
                        <h3><i class="fas fa-bullseye"></i> Areas of Expertise</h3>
                        <div class="about-content">
                            <?php echo nl2br(htmlspecialchars($psych['AreaOfExperties'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="appointment-card">
                <div class="appointment-header">
                    <h3>Book Appointment</h3>
                    <p>Select a date and time for your session</p>
                </div>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($appointmentSuccess): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Appointment booked successfully! You'll receive a confirmation shortly.
                    </div>
                <?php endif; ?>

                <form method="POST" class="appointment-form">
                    <div class="form-group">
                        <label for="appointment_date">
                            <i class="fas fa-calendar"></i> Appointment Date
                        </label>
                        <input type="date" 
                               id="appointment_date" 
                               name="appointment_date" 
                               class="form-control" 
                               required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">
                            <i class="fas fa-clock"></i> Appointment Time
                        </label>
                        <input type="time" 
                               id="appointment_time" 
                               name="appointment_time" 
                               class="form-control" 
                               required>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-calendar-check"></i>
                        Confirm Appointment
                    </button>
                </form>

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Important Information</h4>
                    <ul>
                        <li>Appointments are confirmed via website</li>
                        <li>Please arrive 10 minutes early</li>
                        <li>Cancellation policy: 24 hours notice required</li>
                        <li>Sessions are 50 minutes long</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
        const now = new Date();
        const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
        const formattedTime = nextHour.getHours().toString().padStart(2, '0') + ':00';
        document.getElementById('appointment_time').value = formattedTime;
        <?php if ($appointmentSuccess): ?>
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>