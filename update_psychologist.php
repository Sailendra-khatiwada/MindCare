<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

$psychologist = [];
if (isset($_GET['p_id'])) {
    $p_id = intval($_GET['p_id']);
    $stmt = $conn->prepare("SELECT * FROM psychologist WHERE p_id = ?");
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $psychologist = $result->fetch_assoc();
    $stmt->close();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['p_id'])) {
    $p_id = intval($_GET['p_id']);

    $username = trim($_POST['psychologist_name']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $location = trim($_POST['location']);
    $education = trim($_POST['education']);
    $min_fee = floatval($_POST['min_fee']);
    $max_fee = floatval($_POST['max_fee']);
    $office_start = trim($_POST['office_start']);
    $office_end = trim($_POST['office_end']);
    $contact_info = trim($_POST['contact_info']);
    $description = trim($_POST['description']);
    $experties = trim($_POST['AreaOfExperties']);
    if (
        empty($username) || empty($email) || empty($specialization) || empty($location) ||
        empty($education) || empty($contact_info) || empty($description) || empty($experties)
    ) {
        $error = "All fields including Description are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address!";
    } elseif (!preg_match('/^\d{10,15}$/', $contact_info)) {
        $error = "Contact number must be 10-15 digits!";
    } elseif ($min_fee < 0 || $max_fee < 0 || $min_fee > $max_fee) {
        $error = "Invalid fee structure! Minimum fee cannot be greater than maximum fee.";
    } else {
        $check_stmt = $conn->prepare("SELECT p_id FROM psychologist WHERE email = ? AND p_id != ?");
        $check_stmt->bind_param("si", $email, $p_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "Email already exists. Please use a different email.";
            $check_stmt->close();
        } else {
            $check_stmt->close();

            $update_query = "
                UPDATE psychologist SET 
                username = ?,
                email = ?,
                specialization = ?,
                location = ?,
                education = ?,
                min_fee = ?,
                max_fee = ?,
                office_start = ?,
                office_end = ?,
                contact_info = ?,
                description = ?,
                AreaOfExperties = ?
                WHERE p_id = ?
            ";

            $update_stmt = $conn->prepare($update_query);

            if ($update_stmt) {
                $update_stmt->bind_param(
                    "sssssddsssssi",
                    $username,
                    $email,
                    $specialization,
                    $location,
                    $education,
                    $min_fee,
                    $max_fee,
                    $office_start,
                    $office_end,
                    $contact_info,
                    $description,
                    $experties,
                    $p_id
                );

                if ($update_stmt->execute()) {
                    $success = "Psychologist updated successfully!";
                    $refresh_stmt = $conn->prepare("SELECT * FROM psychologist WHERE p_id = ?");
                    $refresh_stmt->bind_param("i", $p_id);
                    $refresh_stmt->execute();
                    $result = $refresh_stmt->get_result();
                    $psychologist = $result->fetch_assoc();
                    $refresh_stmt->close();
                } else {
                    $error = "Error updating psychologist: " . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $error = "Error preparing update statement: " . $conn->error;
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
    <title>Update Psychologist | MindCare Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/update_psychologist.css">
</head>

<body>
    <div class="container">
        <header class="page-header">
            <a href="dashboard.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <a href="admin_dashboard.php#psychologists" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </header>

        <div class="form-container">
            <div class="form-header">
                <h2>
                    <i class="fas fa-user-edit"></i>
                    Update Psychologist
                </h2>
                <p>Update the details of this mental health professional</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($psychologist): ?>
                <div class="psychologist-info">
                    <img src="<?php echo !empty($psychologist['profile_picture']) ? htmlspecialchars($psychologist['profile_picture']) : 'images/default-profile.jpg'; ?>"
                        alt="Profile"
                        class="profile-avatar">
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($psychologist['username']); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($psychologist['email']); ?></p>
                        <p><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($psychologist['specialization']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($psychologist['location']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="updateForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="psychologist_name">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text"
                            id="psychologist_name"
                            name="psychologist_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['username'] ?? ''); ?>"

                            placeholder="Enter full name">
                        <div class="error-text" id="nameError">
                            <i class="fas fa-exclamation-circle"></i> Name is required
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['email'] ?? ''); ?>"

                            placeholder="Enter email address">
                        <div class="error-text" id="emailError">
                            <i class="fas fa-exclamation-circle"></i> Valid email is required
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="specialization">
                            <i class="fas fa-briefcase"></i> Specialization
                        </label>
                        <input type="text"
                            id="specialization"
                            name="specialization"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['specialization'] ?? ''); ?>"

                            placeholder="Enter specialization">
                    </div>

                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </label>
                        <input type="text"
                            id="location"
                            name="location"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['location'] ?? ''); ?>"

                            placeholder="Enter location">
                    </div>

                    <div class="form-group">
                        <label for="education">
                            <i class="fas fa-graduation-cap"></i> Education
                        </label>
                        <input type="text"
                            id="education"
                            name="education"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['education'] ?? ''); ?>"

                            placeholder="Enter education">
                    </div>

                    <div class="form-row full-width">
                        <div class="form-group">
                            <label for="office_start">
                                <i class="fas fa-clock"></i> Office Start Time
                                <span class="info-tooltip">
                                    <i class="fas fa-info-circle" style="color: var(--medium-gray); font-size: 0.85rem;"></i>
                                    <span class="tooltip-text">Psychologist's daily starting time</span>
                                </span>
                            </label>
                            <input type="time"
                                id="office_start"
                                name="office_start"
                                class="form-control"
                                value="<?php echo htmlspecialchars($psychologist['office_start'] ?? '09:00'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="office_end">
                                <i class="fas fa-clock"></i> Office End Time
                                <span class="info-tooltip">
                                    <i class="fas fa-info-circle" style="color: var(--medium-gray); font-size: 0.85rem;"></i>
                                    <span class="tooltip-text">Psychologist's daily ending time</span>
                                </span>
                            </label>
                            <input type="time"
                                id="office_end"
                                name="office_end"
                                class="form-control"
                                value="<?php echo htmlspecialchars($psychologist['office_end'] ?? '17:00'); ?>">
                        </div>
                    </div>

                    <div class="form-row full-width">
                        <div class="form-group">
                            <label for="min_fee">
                                <i class="fas fa-money-bill-wave"></i> Minimum Fee (Rs.)
                            </label>
                            <input type="number"
                                id="min_fee"
                                name="min_fee"
                                class="form-control"
                                value="<?php echo htmlspecialchars($psychologist['min_fee'] ?? ''); ?>"

                                min="0"
                                placeholder="Enter minimum fee">
                        </div>

                        <div class="form-group">
                            <label for="max_fee">
                                <i class="fas fa-money-bill-wave"></i> Maximum Fee (Rs.)
                            </label>
                            <input type="number"
                                id="max_fee"
                                name="max_fee"
                                class="form-control"
                                value="<?php echo htmlspecialchars($psychologist['max_fee'] ?? ''); ?>"

                                min="0"
                                placeholder="Enter maximum fee">
                        </div>
                    </div>

                    <div class="full-width">
                        <div class="fee-display">
                            <span>Current Fee Range:</span>
                            <strong>Rs. <?php echo number_format($psychologist['min_fee'] ?? 0); ?> - Rs. <?php echo number_format($psychologist['max_fee'] ?? 0); ?></strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact_info">
                            <i class="fas fa-phone"></i> Contact Information
                            <span class="info-tooltip">
                                <i class="fas fa-info-circle" style="color: var(--medium-gray); font-size: 0.85rem;"></i>
                                <span class="tooltip-text">10-15 digits only</span>
                            </span>
                        </label>
                        <input type="text"
                            id="contact_info"
                            name="contact_info"
                            class="form-control"
                            value="<?php echo htmlspecialchars($psychologist['contact_info'] ?? ''); ?>"

                            pattern="[0-9]{10,15}"
                            placeholder="Enter contact number">
                        <div class="error-text" id="contactError">
                            <i class="fas fa-exclamation-circle"></i> 10-15 digits required
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">
                            <i class="fas fa-file-alt"></i> Professional Description
                        </label>
                        <textarea id="description"
                            name="description"
                            class="form-control"
                            rows="4"

                            placeholder="Enter professional description"><?php echo htmlspecialchars($psychologist['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="AreaOfExperties">
                            <i class="fas fa-bullseye"></i> Areas of Expertise
                            <span class="info-tooltip">
                                <i class="fas fa-info-circle" style="color: var(--medium-gray); font-size: 0.85rem;"></i>
                                <span class="tooltip-text">Separate areas with commas</span>
                            </span>
                        </label>
                        <textarea id="AreaOfExperties"
                            name="AreaOfExperties"
                            class="form-control"
                            rows="4"

                            placeholder="Enter areas of expertise (separate with commas)"><?php echo htmlspecialchars($psychologist['AreaOfExperties'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i>
                    Update Psychologist
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('updateForm');
            const submitBtn = document.getElementById('submitBtn');

            if (form) {
                form.addEventListener('submit', function() {
                    // Show loading state only
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin loading"></i> Updating...';
                    submitBtn.disabled = true;
                });
            }

            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);

            // Redirect on success after 3 seconds
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(() => {
                    window.location.href = 'admin_dashboard.php#psychologists';
                }, 3000);
            }
        });
    </script>
</body>

</html>