<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = '';
$success = '';
$user = [];

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare("SELECT username, email, profile_picture, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token mismatch!";
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $new_username = trim($_POST['username']);
            $email = trim($_POST['email']);

            if (empty($new_username)) {
                $error = "Username cannot be empty.";
            } else {
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $stmt->bind_param("si", $new_username, $user_id);
                $stmt->execute();
                $check = $stmt->get_result();
                if ($check->num_rows > 0) {
                    $error = "Username already taken.";
                }
                $stmt->close();

                if (empty($error)) {
                    $profile_picture = $user['profile_picture'];

                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $file_tmp = $_FILES['profile_picture']['tmp_name'];
                        $file_type = mime_content_type($file_tmp);
                        $file_size = $_FILES['profile_picture']['size'];

                        if (!in_array($file_type, $allowed_types)) {
                            $error = "Invalid image type. Only JPG, PNG, GIF, and WebP are allowed.";
                        } elseif ($file_size > 5 * 1024 * 1024) {
                            $error = "Image must be under 5MB.";
                        } else {
                            $upload_dir = "uploads/profile_pictures/";
                            if (!is_dir($upload_dir)) {
                                if (!mkdir($upload_dir, 0755, true)) {
                                    $error = "Failed to create upload directory.";
                                }
                            }

                            if (empty($error)) {
                                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                                $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
                                $destination = $upload_dir . $new_filename;

                                if (move_uploaded_file($file_tmp, $destination)) {
                                    if (!empty($profile_picture) && file_exists($profile_picture)) {
                                        @unlink($profile_picture);
                                    }
                                    $profile_picture = $destination;
                                } else {
                                    $error = "Failed to upload image. Please check folder permissions.";
                                }
                            }
                        }
                    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                        $upload_errors = [
                            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize.",
                            UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE.",
                            UPLOAD_ERR_PARTIAL => "File was only partially uploaded.",
                            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder.",
                            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                            UPLOAD_ERR_EXTENSION => "File upload stopped by extension."
                        ];
                        $error_code = $_FILES['profile_picture']['error'];
                        $error = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : "Unknown upload error.";
                    }

                    if (empty($error)) {
                        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, profile_picture=? WHERE user_id=?");
                        $stmt->bind_param("sssi", $new_username, $email, $profile_picture, $user_id);

                        if ($stmt->execute()) {
                            $_SESSION['username'] = $new_username;
                            $success = "Profile updated successfully!";
                            $user['username'] = $new_username;
                            $user['email'] = $email;
                            $user['profile_picture'] = $profile_picture;
                        } else {
                            $error = "Update failed: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $current = $_POST['current_password'];
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];

            if (empty($current) || empty($new) || empty($confirm)) {
                $error = "All fields required.";
            } elseif ($new !== $confirm) {
                $error = "Passwords do not match.";
            } elseif (strlen($new) < 8) {
                $error = "Password must be at least 8 characters.";
            } else {
                $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                $stmt->close();

                if (password_verify($current, $data['password'])) {
                    $hashed = password_hash($new, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
                    $stmt->bind_param("si", $hashed, $user_id);

                    if ($stmt->execute()) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Password update failed.";
                    }
                    $stmt->close();
                } else {
                    $error = "Current password incorrect.";
                }
            }
        }
    }
}

$appointment_count = 0;
$medication_count = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointment_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM medications WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$medication_count = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | MindCare</title>
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
</head>

<body>
    <header class="profile-header">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark" aria-hidden="true">🕊️</span>
            <span class="brand-text">MindCare</span>
        </a>

        <div class="profile-nav">
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="profile.php" class="nav-link active">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        </div>
    </header>

    <div class="profile-wrapper">
        <h1 class="page-title">
            <i class="fas fa-user-cog"></i>
            Profile Settings
        </h1>

        <p class="page-subtitle">
            Manage your account information and security settings
        </p>

        <?php if (!empty($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">

            <div class="profile-card">
                <div class="profile-avatar">
                    <img id="previewImg" class="profile-img"
                        src="<?php echo !empty($user['profile_picture']) && file_exists($user['profile_picture'])
                                    ? htmlspecialchars($user['profile_picture'])
                                    : 'images/default-profile.jpg'; ?>"
                        alt="Profile Picture">

                    <label class="upload-overlay" for="photoUploadInput" title="Change Profile Picture">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>

                <div class="profile-info">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['username'] ?? $username); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email'] ?? 'No email set'); ?></p>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $appointment_count; ?></span>
                        <span class="stat-label">Appointments</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $medication_count; ?></span>
                        <span class="stat-label">Medications</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">
                            <?php
                            $join_date = new DateTime($user['created_at'] ?? date('Y-m-d'));
                            $now = new DateTime();
                            $interval = $join_date->diff($now);
                            echo $interval->y > 0 ? $interval->y . 'y' : ($interval->m > 0 ? $interval->m . 'm' : $interval->d . 'd');
                            ?>
                        </span>
                        <span class="stat-label">Member</span>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-tabs">
                    <button class="tab-btn active" data-tab="profile">Profile</button>
                    <button class="tab-btn" data-tab="security">Security</button>
                </div>

                <div class="tab-content active" id="profile-tab">
                    <form method="POST" enctype="multipart/form-data" id="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <input type="file" id="photoUploadInput" name="profile_picture" accept="image/*" style="display: none;">

                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Personal Information
                        </h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Username <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text"
                                        class="form-control"
                                        name="username"
                                        value="<?php echo htmlspecialchars($user['username'] ?? $username); ?>"
                                        required
                                        autocomplete="username">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email Address <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email"
                                        class="form-control"
                                        name="email"
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                        required
                                        autocomplete="email">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Member Since</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar"></i>
                                    <input type="text"
                                        class="form-control readonly"
                                        value="<?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?>"
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div class="tab-content" id="security-tab">
                    <form method="POST" id="security-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="change_password">

                        <h3 class="section-title">
                            <i class="fas fa-lock"></i>
                            Password & Security
                        </h3>

                        <div class="form-group">
                            <label>Current Password <span>*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-key"></i>
                                <input type="password"
                                    class="form-control"
                                    name="current_password"
                                    required
                                    autocomplete="current-password"
                                    id="current-password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password <span>*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password"
                                    class="form-control"
                                    name="new_password"
                                    required
                                    autocomplete="new-password"
                                    id="new-password">
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-meter-fill" id="strength-meter-fill"></div>
                                </div>
                                <div class="strength-text" id="strength-text">Password strength</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password <span>*</span></label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password"
                                    class="form-control"
                                    name="confirm_password"
                                    required
                                    autocomplete="new-password"
                                    id="confirm-password">
                            </div>
                            <small id="password-match-message" style="display: none;"></small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                    <p>Permanently delete your account and all associated data. This action cannot be undone.</p>

                    <button class="btn btn-danger" id="deleteAccountBtn">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>

        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });

        const uploadLabel = document.querySelector('.upload-overlay');
        const fileInput = document.getElementById('photoUploadInput');

        if (uploadLabel && fileInput) {
            uploadLabel.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
        }

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
                    this.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            Object.values(requirements).forEach(met => {
                if (met) strength += 20;
            });

            const strengthMeter = document.getElementById('strength-meter-fill');
            const strengthText = document.getElementById('strength-text');

            if (strength <= 40) {
                strengthMeter.className = 'strength-meter-fill weak';
                strengthText.className = 'strength-text weak';
                strengthText.textContent = 'Weak password';
            } else if (strength <= 80) {
                strengthMeter.className = 'strength-meter-fill fair';
                strengthText.className = 'strength-text fair';
                strengthText.textContent = 'Fair password';
            } else {
                strengthMeter.className = 'strength-meter-fill strong';
                strengthText.className = 'strength-text strong';
                strengthText.textContent = 'Strong password';
            }

            strengthMeter.style.width = strength + '%';
        }

        function checkPasswordMatch() {
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const message = document.getElementById('password-match-message');

            if (!confirmPassword) {
                message.style.display = 'none';
                return;
            }

            if (newPassword === confirmPassword) {
                message.textContent = '✓ Passwords match';
                message.style.color = 'var(--success)';
                message.style.display = 'block';
            } else {
                message.textContent = '✗ Passwords do not match';
                message.style.color = 'var(--error)';
                message.style.display = 'block';
            }
        }

        const newPasswordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        }

        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;

                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);

                if (this.id === 'security-form') {
                    const newPassword = document.getElementById('new-password').value;
                    const confirmPassword = document.getElementById('confirm-password').value;

                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }

                    if (newPassword.length < 8) {
                        e.preventDefault();
                        alert('New password must be at least 8 characters long.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }
            });
        });

        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            if (confirm('⚠️ WARNING: This will permanently delete your account and all associated data.\n\nThis action cannot be undone. Are you sure you want to proceed?')) {
                const userInput = prompt('Type "DELETE" to confirm account deletion:');
                if (userInput === 'DELETE') {
                    alert('Account deletion initiated. This feature would delete your account in a real application.');
                    // window.location.href = 'delete_account.php';
                } else {
                    alert('Account deletion cancelled.');
                }
            }
        });
    </script>
</body>

</html>