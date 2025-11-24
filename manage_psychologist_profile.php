<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$error = "";
$success = "";

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['p_id'])) {
    echo "Error: Psychologist ID is not set in session.";
    exit;
}

// Fetch psychologist data
$stmt = $conn->prepare("SELECT * FROM psychologist WHERE p_id = ?");
$stmt->bind_param("i", $_SESSION['p_id']);
$stmt->execute();
$psychologist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$psychologist) {
    echo "Error: Psychologist not found.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        // Sanitize and validate inputs
        $username = trim($_POST['username'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $min_fee = floatval($_POST['min_fee'] ?? 0);
        $max_fee = floatval($_POST['max_fee'] ?? 0);
        $office_start = $_POST['office_start'] ?? '';
        $office_end = $_POST['office_end'] ?? '';
        $profile_picture = $psychologist['profile_picture'];

        // Validation
        if (empty($username) || empty($specialization) || empty($location) || empty($education) || $min_fee <= 0 || $max_fee <= 0 || empty($office_start) || empty($office_end)) {
            $error = "All fields are required and must be valid.";
        } elseif ($min_fee > $max_fee) {
            $error = "Invalid fee structure! Minimum fee cannot be greater than the maximum fee.";
        } else {
            // Handle file upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
                $file_size = $_FILES['profile_picture']['size'];
                $max_size = 2 * 1024 * 1024;

                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $upload_dir = 'images/';
                    $profile_picture = $upload_dir . uniqid('profile_', true) . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
                } else {
                    $error = "Invalid file type or size. Only JPEG, PNG, or GIF under 2MB are allowed.";
                }
            }

            // Update psychologist data
            if (empty($error)) {
                $stmt = $conn->prepare(
                    "UPDATE psychologist SET username = ?, specialization = ?, location = ?, education = ?, min_fee = ?, max_fee = ?, office_start = ?, office_end = ?, profile_picture = ? WHERE p_id = ?"
                );
                $stmt->bind_param(
                    "ssssddsssi",
                    $username,
                    $specialization,
                    $location,
                    $education,
                    $min_fee,
                    $max_fee,
                    $office_start,
                    $office_end,
                    $profile_picture,
                    $_SESSION['p_id']
                );

                if ($stmt->execute()) {
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Error updating profile.";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Profile</title>
    <link rel="stylesheet" href="manage_psychologist_profile.css">
    <script>
        function validateForm() {
            let min_fee = document.forms["profileForm"]["min_fee"].value;
            let max_fee = document.forms["profileForm"]["max_fee"].value;

            min_fee = parseFloat(min_fee);
            max_fee = parseFloat(max_fee);

            if (isNaN(min_fee) || isNaN(max_fee)) {
                alert("Please enter valid numerical values for fees.");
                return false;
            }

            if (min_fee <= 0 || max_fee <= 0) {
                alert("Fees cannot be zero or negative.");
                return false;
            }

            if (min_fee > max_fee) {
                alert("Invalid fee structure! Minimum fee cannot be greater than the maximum fee.");
                return false;
            }

            return true;
        }
    </script>
</head>

<body>
    <div class="form-container">
        <h2>Manage Profile</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form name="profileForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($psychologist['username']); ?>" required>
            </div>

            <div class="form-group">
                <label>Specialization:</label>
                <input type="text" name="specialization" value="<?php echo htmlspecialchars($psychologist['specialization']); ?>" required>
            </div>

            <div class="form-group">
                <label>Location:</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($psychologist['location']); ?>" required>
            </div>

            <div class="form-group">
                <label>Education:</label>
                <input type="text" name="education" value="<?php echo htmlspecialchars($psychologist['education']); ?>" required>
            </div>

            <div class="form-group">
                <label>Minimum Fee:</label>
                <input type="number" name="min_fee" value="<?php echo htmlspecialchars($psychologist['min_fee']); ?>" required>
            </div>

            <div class="form-group">
                <label>Maximum Fee:</label>
                <input type="number" name="max_fee" value="<?php echo htmlspecialchars($psychologist['max_fee']); ?>" required>
            </div>

            <div class="form-group">
                <label>Office Start Time:</label>
                <input type="time" name="office_start" value="<?php echo htmlspecialchars($psychologist['office_start']); ?>" required>
            </div>

            <div class="form-group">
                <label>Office End Time:</label>
                <input type="time" name="office_end" value="<?php echo htmlspecialchars($psychologist['office_end']); ?>" required>
            </div>

            <div class="form-group">
                <label>Profile Picture:</label>
                <input type="file" name="profile_picture">
            </div>

            <button type="submit">Update Profile</button>
        </form>

        <div>
            <h3>Your Current Profile Picture:</h3>
            <?php if (!empty($psychologist['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($psychologist['profile_picture']); ?>" alt="Profile Picture">
            <?php else: ?>
                <img src="default-profile.jpg" alt="Default Profile Picture">
            <?php endif; ?>
        </div>

        <a href="psychologist_dashboard.php"><button type="button">Back Home</button></a>
    </div>
</body>

</html>