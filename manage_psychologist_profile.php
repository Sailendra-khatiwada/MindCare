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
        $contact_info = trim($_POST['contact_info'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $AreaOfExperties = trim($_POST['AreaOfExperties'] ?? '');

        $profile_picture = $psychologist['profile_picture'];

        if ($min_fee > $max_fee) {
            $error = "Minimum fee cannot be greater than maximum fee.";
        } else {
            // File Upload
            if (!empty($_FILES["profile_picture"]["name"])) {
                $allowed = ["image/png", "image/jpeg", "image/jpg", "image/webp"];
                if (in_array($_FILES["profile_picture"]["type"], $allowed)) {
                    $new_name = "uploads/profiles/" . uniqid("PFP_", true) . ".jpg";
                    move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $new_name);
                    $profile_picture = $new_name;
                } else {
                    $error = "Invalid file type. Only JPG, PNG, and WebP are allowed.";
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE psychologist 
                    SET username=?, specialization=?, location=?, education=?, min_fee=?, max_fee=?, 
                        office_start=?, office_end=?, profile_picture=?, contact_info=?, email=?, 
                        description=?, AreaOfExperties=?
                    WHERE p_id=?");
                $stmt->bind_param("ssssddsssssssi", $username, $specialization, $location, $education, 
                    $min_fee, $max_fee, $office_start, $office_end, $profile_picture, 
                    $contact_info, $email, $description, $AreaOfExperties, $_SESSION['p_id']);
                $stmt->execute();
                $stmt->close();

                $success = "Profile updated successfully!";
                // Refresh psychologist data
                $stmt = $conn->prepare("SELECT * FROM psychologist WHERE p_id = ?");
                $stmt->bind_param("i", $_SESSION['p_id']);
                $stmt->execute();
                $psychologist = $stmt->get_result()->fetch_assoc();
                $stmt->close();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile | MindCare</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/manage_psychologist_profile.css">

</head>

<body>
    <div class="container">
        <!-- Header -->
        <header class="page-header">
            <a href="dashboard.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <a href="psychologist_dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </header>

        <!-- Profile Layout -->
        <div class="profile-layout">
            <!-- Sidebar Profile Card -->
            <div class="sidebar-card">
                <img src="<?php echo !empty($psychologist['profile_picture']) ? htmlspecialchars($psychologist['profile_picture']) : 'images/default-profile.jpg'; ?>" 
                     alt="Profile" 
                     class="profile-avatar" 
                     id="profilePreview">
                
                <h2 class="profile-name"><?php echo htmlspecialchars($psychologist['username']); ?></h2>
                <p class="profile-specialization"><?php echo htmlspecialchars($psychologist['specialization']); ?></p>
                
                <div class="profile-stats">
                   
                    <div class="stat-item">
                        <div class="stat-value"><?php echo htmlspecialchars($psychologist['location']); ?></div>
                        <div class="stat-label">Location</div>
                    </div>
                </div>
                
                <div class="file-upload">
                    <input type="file" 
                           id="profileUpload" 
                           name="profile_picture" 
                           class="file-input" 
                           accept="image/*"
                           onchange="previewImage(this)">
                    <label for="profileUpload" class="file-label">
                        <i class="fas fa-camera"></i>
                        Change Photo
                    </label>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="content-card">
                <!-- Tab Header -->
                <div class="tab-header">
                    <button class="tab-btn active" onclick="switchTab('profile')">
                        <i class="fas fa-user-edit"></i> Profile Info
                    </button>
                    <button class="tab-btn" onclick="switchTab('security')">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                </div>

                <!-- Profile Tab -->
                <div id="profileTab" class="tab-content active">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="update_profile">

                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-id-card"></i> Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Username</label>
                                    <input type="text" 
                                           name="username" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['username']); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-briefcase"></i> Specialization</label>
                                    <input type="text" 
                                           name="specialization" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['specialization']); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                                    <input type="text" 
                                           name="location" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['location']); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-graduation-cap"></i> Education</label>
                                    <input type="text" 
                                           name="education" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['education']); ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Contact Info</label>
                                    <input type="text" 
                                           name="contact_info" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['contact_info'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($psychologist['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Practice Details -->
                        <div class="form-section">
                            <h3><i class="fas fa-clinic-medical"></i> Practice Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-clock"></i> Office Hours</label>
                                    <div class="time-range">
                                        <input type="time" 
                                               name="office_start" 
                                               class="form-control" 
                                               value="<?= $psychologist['office_start']; ?>" 
                                               required>
                                        <span class="time-separator">to</span>
                                        <input type="time" 
                                               name="office_end" 
                                               class="form-control" 
                                               value="<?= $psychologist['office_end']; ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-money-bill-wave"></i> Session Fees</label>
                                    <div class="fee-range">
                                        <input type="number" 
                                               name="min_fee" 
                                               class="form-control" 
                                               value="<?= $psychologist['min_fee']; ?>" 
                                               placeholder="Min" 
                                               required>
                                        <span class="fee-separator">to</span>
                                        <input type="number" 
                                               name="max_fee" 
                                               class="form-control" 
                                               value="<?= $psychologist['max_fee']; ?>" 
                                               placeholder="Max" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- About Sections -->
                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> About Sections</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-file-alt"></i> Professional Description</label>
                                    <textarea name="description" 
                                              class="form-control" 
                                              rows="4"><?= htmlspecialchars($psychologist['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-bullseye"></i> Areas of Expertise</label>
                                    <textarea name="AreaOfExperties" 
                                              class="form-control" 
                                              rows="4"><?= htmlspecialchars($psychologist['AreaOfExperties'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="securityTab" class="tab-content">
                    <?php if ($pwd_error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($pwd_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($pwd_success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($pwd_success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="change_password">

                        <div class="form-section">
                            <h3><i class="fas fa-key"></i> Change Password</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Current Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-key"></i>
                                        <input type="password" 
                                               name="old_password" 
                                               class="form-control" 
                                               required 
                                               id="oldPassword">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> New Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" 
                                               name="new_password" 
                                               class="form-control" 
                                               required 
                                               id="newPassword"
                                               onkeyup="checkPasswordStrength()">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Confirm New Password</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-lock"></i>
                                        <input type="password" 
                                               name="confirm_password" 
                                               class="form-control" 
                                               required 
                                               id="confirmPassword"
                                               onkeyup="checkPasswordMatch()">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Requirements -->
                            <div class="password-requirements">
                                <h4>Password Requirements:</h4>
                                <ul>
                                    <li id="req-length">At least 6 characters</li>
                                    <li id="req-match">Passwords must match</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="submit-btn btn-danger">
                                <i class="fas fa-key"></i>
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab Switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Activate clicked button
            event.currentTarget.classList.add('active');
        }
        
        // Image Preview
        function previewImage(input) {
            const preview = document.getElementById('profilePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    
                    // Show animation
                    preview.style.opacity = '0';
                    setTimeout(() => {
                        preview.style.transition = 'opacity 0.3s ease';
                        preview.style.opacity = '1';
                    }, 10);
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        // Password Strength Check
        function checkPasswordStrength() {
            const password = document.getElementById('newPassword').value;
            const lengthReq = document.getElementById('req-length');
            
            if (password.length >= 6) {
                lengthReq.style.color = 'var(--success)';
                lengthReq.innerHTML = '✓ At least 6 characters';
            } else {
                lengthReq.style.color = 'var(--error)';
                lengthReq.innerHTML = '✗ At least 6 characters';
            }
        }
        
        // Password Match Check
        function checkPasswordMatch() {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const matchReq = document.getElementById('req-match');
            
            if (password === confirm && password.length > 0) {
                matchReq.style.color = 'var(--success)';
                matchReq.innerHTML = '✓ Passwords match';
            } else if (confirm.length > 0) {
                matchReq.style.color = 'var(--error)';
                matchReq.innerHTML = '✗ Passwords do not match';
            } else {
                matchReq.style.color = 'var(--medium-gray)';
                matchReq.innerHTML = 'Passwords must match';
            }
        }
        
        // Form Submission Loading
        document.getElementById('profileForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
        });
        
        document.getElementById('passwordForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('.submit-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
        });
        
        // Initialize password checks
        document.addEventListener('DOMContentLoaded', function() {
            checkPasswordStrength();
            checkPasswordMatch();
            
            // Set minimum time values
            const now = new Date();
            const timeInputs = document.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                input.min = '00:00';
                input.max = '23:59';
            });
        });
    </script>
</body>
</html>