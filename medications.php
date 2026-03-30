<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medication_name = trim($_POST['medication_name']);
    $dosage = trim($_POST['dosage']);
    $frequency = $_POST['frequency'] ?? 'Daily';
    $dosage_time = $_POST['dosage_time'] ?? 'Morning';
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?? null;
    $total_days = 0;

    if ($end_date && $start_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $total_days = $start->diff($end)->days + 1;
    }

    $stmt = $conn->prepare("INSERT INTO medications (user_id, medication_name, dosage, start_date, end_date, frequency, dosage_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "issssss",
        $user_id,
        $medication_name,
        $dosage,
        $start_date,
        $end_date,
        $frequency,
        $dosage_time
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Medication added successfully!";
        $_SESSION['message_type'] = 'success';
        header('Location: medications.php');
        exit;
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }

    $stmt->close();
}

$medications = [];
try {
    $sql = "SELECT * FROM medications WHERE user_id = ? ORDER BY start_date DESC, created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Medications fetch error: " . $e->getMessage());
}

$active_meds = 0;
$completed_meds = 0;
$upcoming_meds = 0;
$today = date('Y-m-d');

foreach ($medications as $med) {
    if (empty($med['end_date']) || $med['end_date'] >= $today) {
        $active_meds++;
    } elseif ($med['end_date'] < $today) {
        $completed_meds++;
    }
    if ($med['start_date'] > $today) {
        $upcoming_meds++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Manager | MindCare</title>
    <link rel="stylesheet" href="css/medications.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }

        .stat-icon.active {
            background: linear-gradient(135deg, var(--secondary), #5a8b76);
        }

        .stat-icon.completed {
            background: linear-gradient(135deg, var(--accent), #ff9a8b);
        }

        .stat-icon.upcoming {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #6f42c1, #a370f7);
        }

        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            font-size: 0.9rem;
            color: var(--medium-gray);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-md);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="page-header">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark" aria-hidden="true">🕊️</span>
            <span class="brand-text">MindCare</span>
        </a>

        <div class="user-nav">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <span class="username"><?php echo htmlspecialchars($username); ?></span>
            </div>

            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="medications.php" class="nav-link active">
                    <i class="fas fa-pills"></i> Medications
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <i class="fas fa-pills"></i>
            Medication Manager
        </div>

        <p class="page-subtitle">
            Track and manage your medications with reminders and dosage schedules
        </p>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <i class="fas <?php echo ($_SESSION['message_type'] ?? 'success') == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_meds; ?></h3>
                    <p>Active Medications</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_meds; ?></h3>
                    <p>Completed Courses</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon upcoming">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $upcoming_meds; ?></h3>
                    <p>Upcoming Starts</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($medications); ?></h3>
                    <p>Total Medications</p>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <div class="card form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Medication</h2>
                <div class="card-body">
                    <form method="POST" id="medication-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Medication Name <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-pills"></i>
                                    <input type="text"
                                        name="medication_name"
                                        placeholder="e.g., Aspirin, Metformin"
                                        required
                                        autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Dosage <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-prescription"></i>
                                    <input type="text"
                                        name="dosage"
                                        placeholder="e.g., 500mg, 1 tablet"
                                        required
                                        autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Frequency<span>*</span></label>
                                <select name="frequency">
                                    <option value="Daily">Daily</option>
                                    <option value="Twice Daily">Twice Daily</option>
                                    <option value="Three Times Daily">Three Times Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="As Needed">As Needed</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Preferred Time<span>*</span></label>
                                <select name="dosage_time">
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                    <option value="With Meals">With Meals</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date <span>*</span></label>
                                <input type="date"
                                    name="start_date"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    required>
                            </div>

                            <div class="form-group">
                                <label>End Date <span>*</span></label>
                                <input type="date"
                                    name="end_date"
                                    min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div> 

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-save"></i> Save Medication
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <div class="card list-card">
                <h2><i class="fas fa-list-check"></i> Your Medications</h2>
                <div class="card-body">
                    <?php if (!empty($medications)): ?>
                        <div class="medication-list">
                            <?php foreach ($medications as $medication):
                                $status = 'active';
                                $today = date('Y-m-d');

                                if (!empty($medication['end_date']) && $medication['end_date'] < $today) {
                                    $status = 'completed';
                                } elseif ($medication['start_date'] > $today) {
                                    $status = 'upcoming';
                                }

                                $days_left = 0;
                                if ($status === 'active' && !empty($medication['end_date'])) {
                                    $start = new DateTime($today);
                                    $end = new DateTime($medication['end_date']);
                                    $days_left = $start->diff($end)->days;
                                }
                            ?>
                                <div class="medication-item">
                                    <div class="medication-header">
                                        <strong class="medication-name">
                                            <?php echo htmlspecialchars($medication['medication_name']); ?>
                                        </strong>
                                        
                                    </div>

                                    <div class="medication-details">
                                        <div class="detail-item">
                                            <i class="fas fa-prescription"></i>
                                            <div>
                                                <div class="detail-label">Dosage</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($medication['dosage']); ?></div>
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <div>
                                                <div class="detail-label">Frequency</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($medication['frequency'] ?? 'Daily'); ?></div>
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <div>
                                                <div class="detail-label">Start Date</div>
                                                <div class="detail-value"><?php echo date('M d, Y', strtotime($medication['start_date'])); ?></div>
                                            </div>
                                        </div>

                                        <div class="detail-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <div>
                                                <div class="detail-label">End Date</div>
                                                <div class="detail-value">
                                                    <?php echo $medication['end_date'] ? date('M d, Y', strtotime($medication['end_date'])) : 'Ongoing'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="medication-footer">
                                        <span class="medication-status status-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>

                                        <?php if ($status === 'active' && $days_left > 0): ?>
                                            <span class="medication-days">
                                                <i class="fas fa-hourglass-half"></i>
                                                <?php echo $days_left; ?> days remaining
                                            </span>
                                        <?php elseif ($status === 'upcoming'): ?>
                                            <span class="medication-days">
                                                <i class="fas fa-calendar-plus"></i>
                                                Starts in <?php echo (new DateTime($today))->diff(new DateTime($medication['start_date']))->days; ?> days
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-prescription-bottle-alt"></i>
                            <h3>No Medications Added</h3>
                            <p>You haven't added any medications yet. Start by adding your first medication using the form on the left.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    
        <div class="card schedule-card" style="margin-top: var(--space-xl);">
            <h2><i class="fas fa-calendar-alt"></i> Weekly Medication Schedule</h2>
            <div class="card-body">
                <div class="schedule-grid">
                    <?php
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day):
                    ?>
                        <div class="schedule-day">
                            <h4><?php echo $day; ?></h4>
                            <?php foreach ($medications as $medication):
                                if ($medication['dosage_time'] === 'Morning' || $medication['dosage_time'] === 'With Meals'): ?>
                                    <div class="schedule-time">
                                        <span class="time-label"><?php echo htmlspecialchars($medication['medication_name']); ?></span>
                                        <span class="time-value"><?php echo htmlspecialchars($medication['dosage_time']); ?></span>
                                    </div>
                            <?php endif;
                            endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.getElementById('medication-form').addEventListener('submit', function(e) {
            const medicationName = this.querySelector('[name="medication_name"]').value.trim();
            const dosage = this.querySelector('[name="dosage"]').value.trim();
            const startDate = this.querySelector('[name="start_date"]').value;
            if (!medicationName || !dosage || !startDate) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *)');
                return;
            }

            const endDate = this.querySelector('[name="end_date"]').value;
            if (endDate && new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('End date cannot be before start date');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
        });

        const startDateInput = document.querySelector('[name="start_date"]');
        const endDateInput = document.querySelector('[name="end_date"]');
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
        }

        window.addEventListener('load', function() {
            const today = new Date().toISOString().split('T')[0];
            if (startDateInput && !startDateInput.value) {
                startDateInput.value = today;
            }
            if (endDateInput) {
                endDateInput.min = today;
            }
        });

        const brandMark = document.querySelector('.brand-mark');
        if (brandMark) {
            setInterval(() => {
                brandMark.style.animation = 'float 3s ease-in-out infinite';
            }, 100);
        }

        function convertTableToCards() {
            if (window.innerWidth < 768) {
                const table = document.querySelector('table');
                if (table) {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.forEach(row => {
                        const cells = Array.from(row.querySelectorAll('td'));
                        const medicationCard = document.createElement('div');
                        medicationCard.className = 'medication-item';

                        const medData = cells.map(cell => cell.textContent);
                        medicationCard.innerHTML = `
                            <div class="medication-header">
                                <strong>${medData[0]}</strong>
                            </div>
                            <div class="medication-details">
                                <div class="detail-item">
                                    <i class="fas fa-prescription"></i>
                                    <span>${medData[1]}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>${medData[2]}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>${medData[3]}</span>
                                </div>
                            </div>
                        `;

                        tbody.parentNode.insertBefore(medicationCard, tbody);
                    });

                    table.style.display = 'none';
                }
            }
        }
        window.addEventListener('load', convertTableToCards);
        window.addEventListener('resize', convertTableToCards);
    </script>
</body>
</html>