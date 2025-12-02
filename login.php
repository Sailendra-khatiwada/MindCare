<?php
session_start();
include 'db_connect.php';

$error = "";

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {

        // ---------- ADMIN LOGIN ----------
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

            // ---------- PSYCHOLOGIST LOGIN ----------
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

                // ---------- USER LOGIN ----------
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
    <title>Log In</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-container">
        <form id="login-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <h2>Login</h2>

            <!-- Display error message -->
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <label for="username">Username</label>
            <input type="text" id="username" name="username">

            <label for="password">Password</label>
            <input type="password" id="password" name="password">

            <button type="submit">Login</button>

            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </form>
    </div>
</body>

</html>