<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch data from the database
$users = $conn->query("SELECT * FROM users");
$psychologists = $conn->query("SELECT *FROM psychologist");
$hospitals = $conn->query("SELECT * FROM hospitals");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_dashboard.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <h2>Admin Panel</h2>
            <nav>
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="#psychologist"><i class="fas fa-users"></i> Add Psychologist</a>
                <a href="#hospital"><i class="fas fa-hospital"></i> Manage Hospital</a>
                <a href="#users"><i class="fas fa-users"></i> Manage Users</a>
                <a href="#view-psychologist"><i class="fas fa-users"></i> View Psychologists</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        <div class="footer">
           <?php echo '&copy; ' . date('Y') . ' Admin Dashboard'; ?><br>All Rights Reserved
        </div>
    </div>
    <i class="fas fa-bars" id="MenuBtn"></i>

    <script>
        function confirmAddPsychologist() {
            return confirm("Are you sure you want to Add Psychologist?");
        }

        function confirmAddHospital() {
            return confirm("Are you sure you want to add Hospital?");
        }


        let MenuBtn = document.getElementById('MenuBtn');

        MenuBtn.addEventListener('click', function() {
                document.querySelector('body').classList.toggle('mobile-nav-active');
            }

        );
        window.addEventListener('click', function(e) {
            if (!MenuBtn.contains(e.target) && !document.querySelector('nav ').contains(e.target)) {
                document.body.classList.remove('mobile-nav-active');
            }
        });
    </script>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h1>Welcome, Admin!</h1>
        </div>
        <div id="psychologist">

            <!-- Section for adding a psychologist -->
            <form action="add_psychologist.php" method="POST" onsubmit="return confirmAddPsychologist();">
                <input type="text" name="psychologist_name" placeholder="Psychologist Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="specialization" placeholder="Specialization" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="education" placeholder="Education" required>
                <input type="number" name="min_fee" placeholder="Minimum Fee" required>
                <input type="number" name="max_fee" placeholder="Maximum Fee" required><br><br>
                <input type="time" name="office_start" placeholder="Office Start Time" required>
                <input type="time" name="office_end" placeholder="Office End Time" required><br><br>
                <input type="text" name="contact_info" placeholder="Contact Info" pattern="[0-9]{10}" required>
                <button type="submit">Add Psychologist</button>
            </form>

        </div>
        <!-- Section for managing hospital suggestions -->
        <div id="hospital">
            <h2>Hospital Suggestions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Hospital Name</th>
                        <th>Location</th>
                        <th>Specialization</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($hospital = $hospitals->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $hospital['name']; ?></td>
                            <td><?php echo $hospital['location']; ?></td>
                            <td><?php echo $hospital['specialization']; ?></td>
                            <td><?php echo $hospital['contact_info']; ?></td>
                            <td><a href="remove_hospital.php?hospital_id=<?php echo $hospital['hospital_id']; ?>">Remove</a>
                            <a href="update_hospital.php?hospital_id=<?php echo $hospital['hospital_id']; ?>">Edit</a>
                        </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Section for adding a new hospital -->
            <h3>Add Hospital Suggestion</h3>
            <form action="add_hospital.php" method="POST" onsubmit="return confirmAddHospital();">
                <input type="text" name="name" placeholder="Hospital Name" required>
                <input type="text" name="location" placeholder="Location" required>
                <input type="text" name="specialization" placeholder="Specilization" required>
                <input type="number" name="contact" placeholder="Contact_info" required>
                <button type="submit">Add Hospital</button>
            </form>

        </div>

        <!-- Registered Users -->
        <div id="users">
            <h3>Registered Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Appointment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <a href="view_users_appointments.php?user_id=<?php echo $row['user_id']; ?>">View Appointments</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Psychologists -->
        <div id="view-psychologist">
            <h3>Psychologists</h3>
            <table>
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
                    $psychologists = $conn->query("SELECT p_id, username, email, specialization, location FROM psychologist");
                    while ($psychologist = $psychologists->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $psychologist['username']; ?></td>
                            <td><?php echo $psychologist['email']; ?></td>
                            <td><?php echo $psychologist['specialization']; ?></td>
                            <td><?php echo $psychologist['location']; ?></td>
                            <td>
                                <a href="remove_psychologist.php?p_id=<?php echo $psychologist['p_id']; ?>">Remove</a>
                                <a href="update_psychologist.php?p_id=<?php echo $psychologist['p_id']; ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>