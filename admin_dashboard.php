<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch data
$users = $conn->query("SELECT * FROM users");
$psychologists = $conn->query("SELECT * FROM psychologist");
$hospitals = $conn->query("SELECT * FROM hospitals");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">ADMIN PANEL</div>

        <nav>
            <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#add-psychologist"><i class="fas fa-user-plus"></i> Add Psychologist</a>
            <a href="#hospital"><i class="fas fa-hospital"></i> Hospitals</a>
            <a href="#users"><i class="fas fa-users"></i> Users</a>
            <a href="#view-psychologist"><i class="fas fa-user-md"></i> Psychologists</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <footer>
            &copy; <?= date('Y') ?> Admin
        </footer>
    </aside>

    <!-- MOBILE TOGGLE -->
    <i class="fas fa-bars menu-btn" id="MenuBtn"></i>

    <!-- MAIN CONTENT -->
    <main class="main">
        <header class="main-header">
            <h1>Welcome, Admin</h1>
        </header>

        <!-- ========== ADD PSYCHOLOGIST ========== -->
        <section id="add-psychologist" class="card">
            <h2>Add Psychologist</h2>

            <form action="add_psychologist.php" method="POST" class="form-grid" onsubmit="return confirm('Add this psychologist?')">

                <input type="text" name="psychologist_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="education" placeholder="Education" required>
                <input type="number" name="min_fee" placeholder="Min Fee" required>
                <input type="number" name="max_fee" placeholder="Max Fee" required>

                <input type="time" name="office_start" required>
                <input type="time" name="office_end" required>

                <input type="text" name="contact_info" placeholder="Contact Number (10 digits)" pattern="[0-9]{10}" required>

                <button type="submit" class="btn primary">Add Psychologist</button>
            </form>
        </section>

        <!-- ========== HOSPITALS ========== -->
        <section id="hospital" class="card">
            <h2>Hospital List</h2>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Location</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($h = $hospitals->fetch_assoc()): ?>
                        <tr>
                            <td><?= $h['name'] ?></td>
                            <td><?= $h['location'] ?></td>
                            <td><?= $h['specialization'] ?></td>
                            <td><?= $h['contact_info'] ?></td>
                            <td>
                                <a class="table-btn danger" href="remove_hospital.php?hospital_id=<?= $h['hospital_id'] ?>">Remove</a>
                                <a class="table-btn warning" href="update_hospital.php?hospital_id=<?= $h['hospital_id'] ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <h3>Add Hospital</h3>
            <form action="add_hospital.php" method="POST" class="form-grid" onsubmit="return confirm('Add this hospital?')">
                <input type="text" name="name" placeholder="Hospital Name" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="number" name="contact" placeholder="Contact" required>
                <button type="submit" class="btn primary">Add Hospital</button>
            </form>
        </section>

        <!-- ========== USERS ========== -->
        <section id="users" class="card">
            <h2>Registered Users</h2>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Appointments</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $u['username'] ?></td>
                            <td><?= $u['email'] ?></td>
                            <td>
                                <a class="table-btn info" href="view_users_appointments.php?user_id=<?= $u['user_id'] ?>">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- ========== PSYCHOLOGISTS ========== -->
        <section id="view-psychologist" class="card">
            <h2>Psychologists</h2>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Specialization</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $list = $conn->query("SELECT p_id, username, email, specialization, location FROM psychologist");
                    while ($p = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $p['username'] ?></td>
                            <td><?= $p['email'] ?></td>
                            <td><?= $p['specialization'] ?></td>
                            <td><?= $p['location'] ?></td>
                            <td>
                                <a class="table-btn danger" href="remove_psychologist.php?p_id=<?= $p['p_id'] ?>">Remove</a>
                                <a class="table-btn warning" href="update_psychologist.php?p_id=<?= $p['p_id'] ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

    </main>

    <script>
        let MenuBtn = document.getElementById("MenuBtn");
        MenuBtn.onclick = () => document.body.classList.toggle("open");
    </script>

</body>

</html>
