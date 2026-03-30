<?php
include 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = isset($_POST['user_type']);
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $table = 'users';
                $id_field = 'user_id';
                $redirect = 'login.php';
            }
            $stmt = $conn->prepare("INSERT INTO $table (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='$redirect'>log in</a>.";
                $username = $email = '';
            } else {
                $error = "Error during registration. Please try again.";
            }

            $stmt->close();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | MindCare</title>
    <link rel="stylesheet" href="css/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
</head>

<body>
    <div class="signup-container">
        <div class="signup-header">
            <a href="index.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <p class="welcome-text">Begin your journey towards better mental health with us</p>
        </div>

        <form id="signup-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <h2>Create Your Account</h2>
            <?php if (!empty($error)): ?>
                <div class="error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Choose a username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        
                           autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span>*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Create a strong password"
                           autocomplete="new-password">
                    <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
  
            <button type="submit" id="signup-button">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>

            <p>
                Already have an account? 
                <a href="login.php">Log In Here</a>
            </p>

            <p style="margin-top: 1rem;">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </p>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--medium-gray);">
            <i class="fas fa-shield-alt"></i>
            Your information is protected with end-to-end encryption
        </div>
    </div>

    <script>
        const passwordToggle = document.getElementById('password-toggle');
        const confirmPasswordToggle = document.getElementById('confirm-password-toggle');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        function togglePasswordVisibility(input, toggleBtn) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            const icon = toggleBtn.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                toggleBtn.setAttribute('aria-label', 'Hide password');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggleBtn.setAttribute('aria-label', 'Show password');
            }
        }
        
        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', () => togglePasswordVisibility(passwordInput, passwordToggle));
        }
        
        const signupForm = document.getElementById('signup-form');
        const signupButton = document.getElementById('signup-button');
        
        if (signupForm && signupButton) {
            signupForm.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const terms = document.getElementById('terms').checked;
                
                let errors = [];
                
                if (!username) errors.push('Username is required');
                if (!email) errors.push('Email is required');
                if (!password) errors.push('Password is required');
                if (password.length < 8) errors.push('Password must be at least 8 characters');
                if (password !== confirmPassword) errors.push('Passwords do not match');
                if (!terms) errors.push('You must agree to the terms and conditions');
                
                if (errors.length > 0) {
                    e.preventDefault();
                    showErrors(errors);
                    return;
                }
            
                signupButton.disabled = true;
                signupButton.classList.add('btn-loading');
                signupButton.innerHTML = 'Creating account...';
            });
        }

        function showErrors(errors) {
            const existingError = document.querySelector('.error');
            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.innerHTML = errors.join('<br>');
            const formHeader = signupForm.querySelector('h2');
            if (formHeader) {
                formHeader.parentNode.insertBefore(errorDiv, formHeader.nextSibling);
            }
            errorDiv.style.animation = 'none';
            setTimeout(() => {
                errorDiv.style.animation = 'shake 0.5s ease-in-out';
            }, 10);

            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        window.addEventListener('load', function() {
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                usernameInput.focus();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.target.matches('textarea, select')) {
                e.preventDefault();
                const submitButton = signupForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.click();
                }
            }
        });

        const brandMark = document.querySelector('.brand-mark');
        if (brandMark) {
            setInterval(() => {
                brandMark.style.animation = 'float 3s ease-in-out infinite';
            }, 100);
        }

        const emailInput = document.getElementById('email');
         if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showErrors(['Please enter a valid email address']);
                }
            });
        }
    </script>
</body>
</html>