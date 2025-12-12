<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$psychologists_count = $conn->query("SELECT COUNT(*) as count FROM psychologist")->fetch_assoc()['count'];
$hospitals_count = $conn->query("SELECT COUNT(*) as count FROM hospitals")->fetch_assoc()['count'];
$appointments_count = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];

$recent_users = $conn->query("SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_psychologists = $conn->query("SELECT username, specialization, created_at FROM psychologist ORDER BY created_at DESC LIMIT 5");
$recent_appointments = $conn->query("
    SELECT a.*, u.username as patient, p.username as psychologist 
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN psychologist p ON a.p_id = p.p_id
    ORDER BY appointment_date DESC, appointment_time DESC 
    LIMIT 5
");

$users = $conn->query("SELECT * FROM users");
$psychologists = $conn->query("SELECT * FROM psychologist");
$hospitals = $conn->query("SELECT * FROM hospitals");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MindCare</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <i class="fas fa-bars menu-btn" id="MenuBtn"></i>

    <aside class="sidebar">
        <div class="logo">MindCare Admin</div>
        <nav>
            <a href="#dashboard" class="active" onclick="switchTab('dashboard')">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="#psychologists" onclick="switchTab('psychologists')">
                <i class="fas fa-user-md"></i> Psychologists
            </a>
            <a href="#users" onclick="switchTab('users')">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="#hospitals" onclick="switchTab('hospitals')">
                <i class="fas fa-hospital"></i> Hospitals
            </a>
            <a href="#addPsychologist" onclick="switchTab('addPsychologist')">
                <i class="fas fa-user-plus"></i> Add Psychologist
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        <footer>
            &copy; <?= date('Y') ?> MindCare Admin Panel
        </footer>
    </aside>

    <main class="main">
        <header class="main-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-info">
                <div class="admin-avatar">A</div>
                <div>
                    <h3 style="font-size: 1rem; margin-bottom: 0.125rem;">Administrator</h3>
                    <p style="font-size: 0.85rem; color: var(--medium-gray);"><?php echo date('F j, Y'); ?></p>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $users_count; ?></h3>
                    <p>Registered Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $psychologists_count; ?></h3>
                    <p>Psychologists</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $hospitals_count; ?></h3>
                    <p>Hospitals</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $appointments_count; ?></h3>
                    <p>Appointments</p>
                </div>
            </div>
        </div>
        <div class="recent-grid">
            <div class="recent-card">
                <div class="recent-header">
                    <h3><i class="fas fa-user-clock"></i> Recent Users</h3>
                    <a href="#users" onclick="switchTab('users')" style="color: var(--primary); font-size: 0.85rem;">View All</a>
                </div>
                <div class="recent-list">
                    <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <div class="recent-item">
                            <div class="recent-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="recent-content">
                                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                                <p><?php echo htmlspecialchars($user['email']); ?> • <?php echo date('M d', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div class="recent-card">
                <div class="recent-header">
                    <h3><i class="fas fa-calendar-alt"></i> Recent Appointments</h3>
                    <span style="color: var(--primary); font-size: 0.85rem;">Latest 5</span>
                </div>
                <div class="recent-list">
                    <?php while ($app = $recent_appointments->fetch_assoc()): ?>
                        <div class="recent-item">
                            <div class="recent-icon" style="background: var(--success);">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="recent-content">
                                <h4><?php echo htmlspecialchars($app['patient']); ?></h4>
                                <p>With <?php echo htmlspecialchars($app['psychologist']); ?> • <?php echo date('M d', strtotime($app['appointment_date'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('dashboard')">Dashboard</button>
            <button class="tab-btn" onclick="switchTab('psychologists')">Psychologists</button>
            <button class="tab-btn" onclick="switchTab('users')">Users</button>
            <button class="tab-btn" onclick="switchTab('hospitals')">Hospitals</button>
            <button class="tab-btn" onclick="switchTab('addPsychologist')">Add Psychologist</button>
        </div>
        <div id="dashboardTab" class="tab-content active">
            <div class="card">
                <h2><i class="fas fa-chart-bar"></i> System Overview</h2>
                <p>Welcome to MindCare Admin Panel. Here you can manage all aspects of the platform.</p>
            </div>
        </div>
        <div id="psychologistsTab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-user-md"></i> Psychologists Management</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Location</th>
                                <th>Fees</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $psychologists->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['username']) ?></td>
                                    <td><?= htmlspecialchars($p['email']) ?></td>
                                    <td><?= htmlspecialchars($p['specialization']) ?></td>
                                    <td><?= htmlspecialchars($p['location']) ?></td>
                                    <td>Rs. <?= number_format($p['min_fee']) ?> - <?= number_format($p['max_fee']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="update_psychologist.php?p_id=<?= $p['p_id'] ?>" class="action-btn btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="remove_psychologist.php?p_id=<?= $p['p_id'] ?>"
                                                class="action-btn btn-delete"
                                                onclick="return confirm('Are you sure you want to remove this psychologist?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="usersTab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-users"></i> Registered Users</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users->data_seek(0);
                            while ($u = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <a href="view_users_appointments.php?user_id=<?= $u['user_id'] ?>" class="action-btn btn-view">
                                            <i class="fas fa-eye"></i> View Appointments
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="hospitalsTab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-hospital"></i> Hospital Management</h2>

                 <div class="form-section">
                    <h3><i class="fas fa-plus-circle"></i> Add New Hospital</h3>
                    <form action="add_hospital.php" method="POST" class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hospital"></i> Hospital Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter hospital name" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Enter location" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-stethoscope"></i> Specialization</label>
                            <input type="text" name="specialization" class="form-control" placeholder="Enter specialization" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Contact Info</label>
                            <input type="text" name="contact" class="form-control" placeholder="Enter contact number" required>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Hospital
                            </button>
                        </div>
                    </form>
                </div>
             
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Hospital</th>
                                <th>Location</th>
                                <th>Specialization</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($h = $hospitals->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['name']) ?></td>
                                    <td><?= htmlspecialchars($h['location']) ?></td>
                                    <td><?= htmlspecialchars($h['specialization']) ?></td>
                                    <td><?= htmlspecialchars($h['contact_info']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="update_hospital.php?hospital_id=<?= $h['hospital_id'] ?>" class="action-btn btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="remove_hospital.php?hospital_id=<?= $h['hospital_id'] ?>"
                                                class="action-btn btn-delete"
                                                onclick="return confirm('Are you sure you want to remove this hospital?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <div id="addPsychologistTab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Add New Psychologist</h2>

                <form action="add_psychologist.php" method="POST" class="form-grid" onsubmit="return confirm('Add this psychologist?')">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" name="psychologist_name" class="form-control" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-briefcase"></i> Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="Enter specialization" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Enter location" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Education</label>
                        <input type="text" name="education" class="form-control" placeholder="Enter education" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Minimum Fee</label>
                        <input type="number" name="min_fee" class="form-control" placeholder="Enter minimum fee" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Maximum Fee</label>
                        <input type="number" name="max_fee" class="form-control" placeholder="Enter maximum fee" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Office Start Time</label>
                        <input type="time" name="office_start" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Office End Time</label>
                        <input type="time" name="office_end" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contact Info</label>
                        <input type="text" name="contact_info" class="form-control" placeholder="Enter contact number" pattern="[0-9]{10}" required>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label><i class="fas fa-file-alt"></i> Description</label>
                        <textarea name="description" class="form-control" placeholder="Enter professional description" required></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label><i class="fas fa-bullseye"></i> Areas of Expertise</label>
                        <textarea name="AreaOfExperties" class="form-control" placeholder="Enter areas of expertise" required></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Psychologist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const MenuBtn = document.getElementById("MenuBtn");
        MenuBtn.onclick = () => document.body.classList.toggle("open");

        function switchTab(tabName) {
            window.location.hash = tabName;
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(tabName + 'Tab').classList.add('active');
            event.currentTarget.classList.add('active');
            document.querySelectorAll('.sidebar nav a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + tabName) {
                    link.classList.add('active');
                }
            });
            if (window.innerWidth <= 1024) {
                document.body.classList.remove('open');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1) || 'dashboard';
            switchTab(hash);
            const timeInputs = document.querySelectorAll('input[type="time"]');
            timeInputs.forEach(input => {
                if (!input.value) {
                    if (input.name === 'office_start') {
                        input.value = '09:00';
                    } else if (input.name === 'office_end') {
                        input.value = '17:00';
                    }
                }
            });
        });
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const minFee = this.querySelector('input[name="min_fee"]');
                const maxFee = this.querySelector('input[name="max_fee"]');
                if (minFee && maxFee && parseFloat(minFee.value) > parseFloat(maxFee.value)) {
                    e.preventDefault();
                    alert('Minimum fee cannot be greater than maximum fee.');
                    minFee.focus();
                }
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                }
            });
        });
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>