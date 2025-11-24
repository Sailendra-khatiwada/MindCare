<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

// Initialize CSRF token if it is not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "Error: Invalid CSRF token.";
        exit;
    }

    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $profile_picture = '';

    // Fetch the current user's data
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/avif', 'image/jpg'];
        $max_size = 2 * 1024 * 1024;

        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        $file_size = $_FILES['profile_picture']['size'];

        if (!in_array($file_type, $allowed_types) || $file_size > $max_size) {
            echo "Error: Invalid file type or size.";
            exit;
        }

        $upload_dir = 'images/';
        $profile_picture = $upload_dir . uniqid('profile_', true) . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
    } else {
        $profile_picture = $user['profile_picture'];
    }

    // Update the database
    $stmt = $conn->prepare("UPDATE users SET username=?, profile_picture=? WHERE user_id=?");
    $stmt->bind_param("ssi", $username, $profile_picture, $_SESSION['user_id']);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header('Location: profile.php');
        exit;
    } else {
        echo "Error updating profile.";
    }
    $stmt->close();
}

// Fetch the latest user data
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
    <title>Manage Profile</title>
    <link rel="stylesheet" href="profile.css">
</head>

<body>

    <body>

        <div class="form-container">
            <h2>Manage Profile</h2>
            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required placeholder="Username"><br>
                <label for="profile_picture">Profile Picture:</label>
                <input type="file" name="profile_picture"><br>
                <button type="submit">Update Profile</button>
            </form>

            <div>
                <h3>Your Current Profile Picture:</h3>
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" >
                <?php else: ?>
                    <img src="default-profile.jpg" alt="Default Profile Picture" style="width: 50px; height: auto; border-radius: 50%;">
                <?php endif; ?>
                <a href="dashboard.php"><button type="button">Back Home</button></a>
            </div>
        </div>
    </body>

</body>

</html>