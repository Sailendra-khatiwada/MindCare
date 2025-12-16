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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT username, email, profile_picture,created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Profile fetch error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'update_profile';
        
        if ($action === 'update_profile') {
            // Update profile information
            $new_username = trim($_POST['username']);
            $email = trim($_POST['email']);
            
            
            // Validate username
            if (empty($new_username)) {
                $error = 'Username cannot be empty.';
            } else {
                // Check if username is taken (excluding current user)
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
                $stmt->bind_param("si", $new_username, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Username is already taken.';
                } else {
                    // Handle profile picture upload
                    $profile_picture = $user['profile_picture'];
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
                        
                        if (in_array($file_type, $allowed_types)) {
                            $max_size = 5 * 1024 * 1024; // 5MB
                            if ($_FILES['profile_picture']['size'] <= $max_size) {
                                $upload_dir = 'uploads/profile_pictures/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }
                                
                                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                                $destination = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                                    // Delete old profile picture if it exists and is not default
                                    if ($profile_picture && $profile_picture !== 'default-profile.jpg' && file_exists($profile_picture)) {
                                        unlink($profile_picture);
                                    }
                                    $profile_picture = $destination;
                                } else {
                                    $error = 'Failed to upload profile picture.';
                                }
                            } else {
                                $error = 'Profile picture must be less than 5MB.';
                            }
                        } else {
                            $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                        }
                    }
                    
                    if (empty($error)) {
                        // Update user information
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE user_id = ?");
                        $stmt->bind_param("sssssi", $new_username, $email, $profile_picture, $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['username'] = $new_username;
                            $user['username'] = $new_username;
                            $user['email'] = $email;
                            $user['profile_picture'] = $profile_picture;
                            $success = 'Profile updated successfully!';
                        } else {
                            $error = 'Failed to update profile: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
                $stmt->close();
            }
        } elseif ($action === 'change_password') {
            // Change password
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new_password) < 8) {
                $error = 'New password must be at least 8 characters long.';
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                
                if (password_verify($current_password, $user_data['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Current password is incorrect.';
                }
                $stmt->close();
            }
        } 
    }
}

// Count user statistics
$appointment_count = 0;
$medication_count = 0;
try {
    // Count appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment_count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    
    // Count medications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM medications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medication_count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Profile statistics error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | MindCare</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/profile.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
</head>

<body>

    <!-- Header -->
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

        <!-- Page Title -->
        <h1 class="page-title">
            <i class="fas fa-user-cog"></i>
            Profile Settings
        </h1>
        
        <p class="page-subtitle">
            Manage your account information and security settings
        </p>

        <!-- Alerts -->
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

            <!-- Left Column: Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <img id="previewImg" class="profile-img"
                         src="<?php echo !empty($user['profile_picture']) && file_exists($user['profile_picture']) 
                             ? htmlspecialchars($user['profile_picture']) 
                             : 'images/default-profile.jpg'; ?>" 
                         alt="Profile Picture">
                    
                    <label class="upload-overlay" for="photoUpload" title="Change Profile Picture">
                        <i class="fas fa-camera"></i>
                        <input type="file" id="photoUpload" name="profile_picture" accept="image/*">
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

            <!-- Right Column: Settings Card -->
            <div class="settings-card">
                <div class="settings-tabs">
                    <button class="tab-btn active" data-tab="profile">Profile</button>
                    <button class="tab-btn" data-tab="security">Security</button>
                </div>

                <!-- Profile Tab -->
                <div class="tab-content active" id="profile-tab">
                    <form method="POST" enctype="multipart/form-data" id="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
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

                <!-- Security Tab -->
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

                <!-- Danger Zone -->
                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                    <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                    
                    <button class="btn btn-danger" id="deleteAccountBtn">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </div>

        </div>

        <!-- Back Link -->
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Profile picture preview
        document.getElementById('photoUpload').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB)
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

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 6,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            // Count met requirements
            Object.values(requirements).forEach(met => {
                if (met) strength += 20;
            });

            // Update strength meter
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

        // Check password match
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

        // Password validation listeners
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

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                
                // Re-enable button after 5 seconds (in case submission fails)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
                
                // Additional validation for security form
                if (this.id === 'security-form') {
                    const currentPassword = document.getElementById('current-password').value;
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
                        alert('New password must be at least 6 characters long.');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        return;
                    }
                }
            });
        });

        // Delete account confirmation
        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            if (confirm('⚠️ WARNING: This will permanently delete your account and all associated data.\n\nThis action cannot be undone. Are you sure you want to proceed?')) {
                if (confirm('To confirm, please type "DELETE" to proceed:')) {
                    const userInput = prompt('Type "DELETE" to confirm account deletion:');
                    if (userInput === 'DELETE') {
                        // In a real application, you would make an AJAX call here
                        // For now, we'll just show a message
                        alert('Account deletion initiated. This feature would delete your account in a real application.');
                        // window.location.href = 'delete_account.php';
                    } else {
                        alert('Account deletion cancelled.');
                    }
                }
            }
        });

        // Add floating animation to brand mark
        const brandMark = document.querySelector('.brand-mark');
        if (brandMark) {
            setInterval(() => {
                brandMark.style.animation = 'float 3s ease-in-out infinite';
            }, 100);
        }

        // Auto-save indicator
        let saveTimeout;
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Show auto-save indicator
                    const indicator = document.createElement('div');
                    indicator.style.cssText = `
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: var(--success);
                        color: white;
                        padding: 0.5rem 1rem;
                        border-radius: var(--radius-md);
                        box-shadow: var(--shadow-md);
                        z-index: 1000;
                        animation: fadeIn 0.3s ease-out;
                    `;
                    indicator.textContent = 'Auto-saving...';
                    document.body.appendChild(indicator);
                    
                    setTimeout(() => {
                        indicator.style.animation = 'fadeOut 0.3s ease-out forwards';
                        setTimeout(() => indicator.remove(), 300);
                    }, 2000);
                }, 2000); // Auto-save after 2 seconds of inactivity
            });
        });

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>