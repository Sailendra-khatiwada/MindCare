<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF Token");
    }

    $username = htmlspecialchars($_POST['username']);
    $profile_picture = '';
    $change_password = false;

    // Fetch old data
    $stmt = $conn->prepare("SELECT password, profile_picture FROM users WHERE user_id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    /* ------------------ HANDLE PROFILE PICTURE UPLOAD ------------------ */
    if (!empty($_FILES['profile_picture']['name'])) {

        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/avif'];
        $max = 2 * 1024 * 1024;

        if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
            $error = "Invalid file type.";
        } elseif ($_FILES['profile_picture']['size'] > $max) {
            $error = "Image is too large.";
        } else {
            $upload_dir = "images/";
            $profile_picture = $upload_dir . uniqid("profile_", true) . "." . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
        }
    } else {
        $profile_picture = $user_old['profile_picture'];
    }

    /* ------------------ HANDLE PASSWORD CHANGE ------------------ */
    if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {

        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($current, $user_old['password'])) {
            $error = "Incorrect current password.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($new) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $new_hashed = password_hash($new, PASSWORD_BCRYPT);
            $change_password = true;
        }
    }

    if (empty($error)) {
        if ($change_password) {
            $stmt = $conn->prepare("UPDATE users SET username=?, profile_picture=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssi", $username, $profile_picture, $new_hashed, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, profile_picture=? WHERE user_id=?");
            $stmt->bind_param("ssi", $username, $profile_picture, $_SESSION['user_id']);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
        }
        $stmt->close();
    }
}

/* ------------------ FETCH UPDATED USER DATA ------------------ */
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile Settings</title>
    <link rel="stylesheet" href="profile.css">
</head>

<body>

<div class="profile-wrapper">

    <h2 class="page-title">Profile Settings</h2>

    <?php if (!empty($success)): ?>
        <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="profile-grid">

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <!-- LEFT COLUMN: PHOTO -->
        <div class="photo-card">
            <img id="previewImg" class="profile-img"
                 src="<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'default-profile.jpg'; ?>" />

            <label for="photoUpload" class="upload-btn">Upload New Photo</label>
            <input type="file" id="photoUpload" name="profile_picture" accept="image/*">
        </div>

        <!-- RIGHT COLUMN: INFO -->
        <div class="info-card">

            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

            <h3 class="section-title">Change Password</h3>

            <label>Current Password</label>
            <input type="password" name="current_password">

            <label>New Password</label>
            <input type="password" name="new_password">

            <label>Confirm Password</label>
            <input type="password" name="confirm_password">

            <button class="save-btn">Save Changes</button>
        </div>

    </form>

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

</div>


<script>
document.getElementById("photoUpload").addEventListener("change", function() {
    const file = this.files[0];
    if (file){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById("previewImg").src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>
