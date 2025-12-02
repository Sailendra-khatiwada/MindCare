<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$error = "";
$success = "";
$pwd_success = "";
$pwd_error = "";

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['p_id'])) {
    echo "Error: Psychologist ID missing.";
    exit;
}

// Fetch psychologist info
$stmt = $conn->prepare("SELECT * FROM psychologist WHERE p_id = ?");
$stmt->bind_param("i", $_SESSION['p_id']);
$stmt->execute();
$psychologist = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --------- PROFILE UPDATE ---------
if (isset($_POST['update_profile'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $specialization = trim($_POST['specialization']);
        $location = trim($_POST['location']);
        $education = trim($_POST['education']);
        $min_fee = floatval($_POST['min_fee']);
        $max_fee = floatval($_POST['max_fee']);
        $office_start = $_POST['office_start'];
        $office_end = $_POST['office_end'];

        $profile_picture = $psychologist['profile_picture'];

        if ($min_fee > $max_fee) {
            $error = "Minimum fee cannot be greater than maximum fee.";
        } else {
            // File Upload
            if (!empty($_FILES["profile_picture"]["name"])) {
                $allowed = ["image/png", "image/jpeg", "image/jpg"];
                if (in_array($_FILES["profile_picture"]["type"], $allowed)) {
                    $new_name = "images/" . uniqid("PFP_", true) . ".jpg";
                    move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $new_name);
                    $profile_picture = $new_name;
                } else {
                    $error = "Invalid file type.";
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE psychologist 
                    SET username=?, specialization=?, location=?, education=?, min_fee=?, max_fee=?, office_start=?, office_end=?, profile_picture=? 
                    WHERE p_id=?");
                $stmt->bind_param("ssssddsssi", $username, $specialization, $location, $education, $min_fee, $max_fee, $office_start, $office_end, $profile_picture, $_SESSION['p_id']);
                $stmt->execute();
                $stmt->close();

                $success = "Profile updated successfully!";
            }
        }
    }
}

// --------- CHANGE PASSWORD ---------
if (isset($_POST['change_password'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $pwd_error = "Invalid CSRF token.";
    } else {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($old, $psychologist['password'])) {
            $pwd_error = "Old password is incorrect.";
        } elseif ($new !== $confirm) {
            $pwd_error = "Passwords do not match.";
        } elseif (strlen($new) < 6) {
            $pwd_error = "New password must be at least 6 characters.";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE psychologist SET password=? WHERE p_id=?");
            $stmt->bind_param("si", $hashed, $_SESSION['p_id']);
            $stmt->execute();
            $stmt->close();

            $pwd_success = "Password changed successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Profile</title>
<link rel="stylesheet" href="css/manage_psychologist_profile.css">
</head>

<body>
<div class="container">

    <div class="card">
        <h2>Psychologist Profile</h2>

        <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="update_profile">

            <div class="grid">
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= $psychologist['username'] ?>" required>
                </div>
                <div>
                    <label>Specialization</label>
                    <input type="text" name="specialization" value="<?= $psychologist['specialization'] ?>" required>
                </div>
                <div>
                    <label>Location</label>
                    <input type="text" name="location" value="<?= $psychologist['location'] ?>" required>
                </div>
                <div>
                    <label>Education</label>
                    <input type="text" name="education" value="<?= $psychologist['education'] ?>" required>
                </div>

                <div>
                    <label>Min Fee</label>
                    <input type="number" name="min_fee" value="<?= $psychologist['min_fee'] ?>" required>
                </div>

                <div>
                    <label>Max Fee</label>
                    <input type="number" name="max_fee" value="<?= $psychologist['max_fee'] ?>" required>
                </div>

                <div>
                    <label>Office Start Time</label>
                    <input type="time" name="office_start" value="<?= $psychologist['office_start'] ?>" required>
                </div>

                <div>
                    <label>Office End Time</label>
                    <input type="time" name="office_end" value="<?= $psychologist['office_end'] ?>" required>
                </div>
            </div>

            <label>Change Profile Picture</label>
            <input type="file" name="profile_picture">

            <button class="btn primary">Update Profile</button>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="card">
        <h2>Change Password</h2>

        <?php if ($pwd_error): ?><div class="alert error"><?= $pwd_error ?></div><?php endif; ?>
        <?php if ($pwd_success): ?><div class="alert success"><?= $pwd_success ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="change_password">

            <label>Old Password</label>
            <input type="password" name="old_password" required>

            <label>New Password</label>
            <input type="password" name="new_password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <button class="btn danger">Update Password</button>
        </form>
    </div>

    <a href="psychologist_dashboard.php" class="back-btn">Back to Dashboard</a>

</div>
</body>
</html>
