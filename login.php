<?php
session_start();
include 'db_connect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if ($password == $admin['password']) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $stmt = $conn->prepare("SELECT p_id, username, password FROM psychologist WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $psychologist = $result->fetch_assoc();
                if (password_verify($password, $psychologist['password'])) {
                    $_SESSION['p_id'] = $psychologist['p_id'];
                    $_SESSION['username'] = $psychologist['username'];
                    header('Location: psychologist_dashboard.php');
                    exit;
                } else {
                    $error = "Incorrect password!";
                }
            } else {
                $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = "Incorrect password!";
                    }
                } else {
                    $error = "No user found with that username!";
                }
            }
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
    <title>Log In | MindCare</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <a href="index.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare</span>
            </a>
            <p class="welcome-text">Welcome back to your mental health journey</p>
        </div>

        <form id="login-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <h2>Login to Your Account</h2>

            <?php if (!empty($error)): ?>
                <div class="error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required
                        autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label for="password">Password</label>
                </div>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" id="login-button">
                <i class="fas fa-sign-in-alt"></i>
                Log In
            </button>
            <p>
                Don't have an account?
                <a href="signup.php">Create Account</a>
            </p>

            <p style="margin-top: 1rem;">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </p>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--medium-gray);">
            <i class="fas fa-shield-alt"></i>
            Your login is secured with encryption
        </div>
    </div>

    <script>
        const passwordToggle = document.getElementById('password-toggle');
        const passwordInput = document.getElementById('password');
        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        }
        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-button');

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();

                if (!username || !password) {
                    e.preventDefault();
                    showError('Please fill in all fields');
                    return;
                }

                loginButton.disabled = true;
                loginButton.classList.add('btn-loading');
                loginButton.innerHTML = 'Logging in...';
                setTimeout(() => {
                    loginButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Log In';
                    loginButton.classList.remove('btn-loading');
                    loginButton.disabled = false;
                }, 2000);
            });
        }

        function showError(message) {
            const existingError = document.querySelector('.error');
            if (existingError) {
                existingError.remove();
            }
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.innerHTML = message;
            const formHeader = loginForm.querySelector('h2');
            if (formHeader) {
                formHeader.parentNode.insertBefore(errorDiv, formHeader.nextSibling);
            }
            errorDiv.style.animation = 'none';
            setTimeout(() => {
                errorDiv.style.animation = 'shake 0.5s ease-in-out';
            }, 10);
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
                const submitButton = loginForm.querySelector('button[type="submit"]');
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
    </script>
</body>

</html>