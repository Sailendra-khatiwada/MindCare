<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch data with counts
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$psychologists_count = $conn->query("SELECT COUNT(*) as count FROM psychologist")->fetch_assoc()['count'];
$hospitals_count = $conn->query("SELECT COUNT(*) as count FROM hospitals")->fetch_assoc()['count'];
$appointments_count = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];

// Recent data
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

// Fetch all data for tables
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
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
    
    <style>
        :root {
            /* Color Palette */
            --primary: #4a7b9d;
            --primary-light: #7ba6c1;
            --primary-dark: #2c5a78;
            --secondary: #6a9c89;
            --accent: #e8a87c;
            --light: #f8f9fa;
            --light-gray: #e9ecef;
            --medium-gray: #adb5bd;
            --dark: #2d3748;
            --white: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --purple: #8b5cf6;
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-sidebar: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            --gradient-card: linear-gradient(135deg, var(--white) 0%, #fcfdfe 100%);
            --gradient-success: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            --gradient-info: linear-gradient(135deg, var(--info) 0%, #1d4ed8 100%);
            --gradient-purple: linear-gradient(135deg, var(--purple) 0%, #7c3aed 100%);
            
            /* Shadows */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.12);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 50%;
            
            /* Spacing */
            --space-xs: 0.5rem;
            --space-sm: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: var(--dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--gradient-sidebar);
            color: var(--white);
            padding: var(--space-lg);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: var(--shadow-xl);
            transition: transform 0.3s ease;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-md);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .logo:before {
            content: "🕊️";
            font-size: 1.8rem;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.875rem 1rem;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .sidebar nav a.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .sidebar nav a i {
            width: 24px;
            font-size: 1.1rem;
        }

        .sidebar footer {
            margin-top: auto;
            padding-top: var(--space-lg);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Main Content */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: var(--space-lg);
            transition: margin-left 0.3s ease;
        }

        /* Mobile Menu Button */
        .menu-btn {
            display: none;
            position: fixed;
            top: 1.5rem;
            left: 1.5rem;
            z-index: 1000;
            background: var(--primary);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        /* Top Header */
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-md);
            border-bottom: 2px solid var(--light-gray);
        }

        .main-header h1 {
            font-size: 2rem;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            background: var(--white);
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--light-gray);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
        }

        .stat-card {
            background: var(--gradient-card);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card:nth-child(1) { border-top: 4px solid var(--info); }
        .stat-card:nth-child(2) { border-top: 4px solid var(--success); }
        .stat-card:nth-child(3) { border-top: 4px solid var(--purple); }
        .stat-card:nth-child(4) { border-top: 4px solid var(--warning); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }

        .stat-card:nth-child(1) .stat-icon { background: var(--gradient-info); }
        .stat-card:nth-child(2) .stat-icon { background: var(--gradient-success); }
        .stat-card:nth-child(3) .stat-icon { background: var(--gradient-purple); }
        .stat-card:nth-child(4) .stat-icon { background: var(--gradient-warning); }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .stat-content p {
            font-size: 0.9rem;
            color: var(--medium-gray);
            font-weight: 500;
        }

        /* Content Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--light-gray);
            margin-bottom: var(--space-xl);
            animation: fadeIn 0.5s ease-out;
        }

        .card h2 {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card h2 i {
            color: var(--primary);
        }

        /* Tabs Navigation */
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--light-gray);
            margin-bottom: var(--space-lg);
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 1rem 2rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: var(--medium-gray);
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--primary);
        }

        .tab-btn.active {
            color: var(--primary-dark);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        /* Form Styles */
        .form-section {
            margin-bottom: var(--space-xl);
        }

        .form-section h3 {
            font-size: 1.25rem;
            color: var(--primary-dark);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-md);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-md);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(74, 123, 157, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            grid-column: 1 / -1;
        }

        /* Buttons */
        .btn {
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--light-gray);
            margin-bottom: var(--space-lg);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background: var(--primary);
            color: var(--white);
        }

        th {
            padding: 1rem 1.25rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid var(--light-gray);
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: var(--light);
        }

        td {
            padding: 1rem 1.25rem;
            font-size: 0.95rem;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .btn-edit {
            background: var(--warning);
            color: var(--white);
        }

        .btn-edit:hover {
            background: #d97706;
        }

        .btn-delete {
            background: var(--error);
            color: var(--white);
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-view {
            background: var(--info);
            color: var(--white);
        }

        .btn-view:hover {
            background: #2563eb;
        }

        /* Recent Activity */
        .recent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
        }

        .recent-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--light-gray);
        }

        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }

        .recent-header h3 {
            font-size: 1.25rem;
            color: var(--primary-dark);
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }

        .recent-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm);
            border-radius: var(--radius-md);
            background: var(--light);
            border-left: 4px solid var(--primary);
        }

        .recent-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .recent-content h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .recent-content p {
            font-size: 0.85rem;
            color: var(--medium-gray);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            body.open .sidebar {
                transform: translateX(0);
            }
            
            .main {
                margin-left: 0;
            }
            
            .menu-btn {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .main {
                padding: var(--space-md);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-header {
                flex-direction: column;
                gap: var(--space-md);
                align-items: flex-start;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                overflow-x: auto;
                padding-bottom: var(--space-xs);
            }
            
            .recent-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-wrapper {
                margin: 0 -1rem;
                border-radius: 0;
            }
            
            .admin-info {
                align-self: flex-start;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Button -->
    <i class="fas fa-bars menu-btn" id="MenuBtn"></i>

    <!-- Sidebar -->
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
            <a href="#add-psychologist" onclick="switchTab('add-psychologist')">
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

    <!-- Main Content -->
    <main class="main">
        <!-- Top Header -->
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

        <!-- Stats Grid -->
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

        <!-- Recent Activity -->
        <div class="recent-grid">
            <div class="recent-card">
                <div class="recent-header">
                    <h3><i class="fas fa-user-clock"></i> Recent Users</h3>
                    <a href="#users" onclick="switchTab('users')" style="color: var(--primary); font-size: 0.85rem;">View All</a>
                </div>
                <div class="recent-list">
                    <?php while($user = $recent_users->fetch_assoc()): ?>
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
                    <?php while($app = $recent_appointments->fetch_assoc()): ?>
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

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('dashboard')">Dashboard</button>
            <button class="tab-btn" onclick="switchTab('psychologists')">Psychologists</button>
            <button class="tab-btn" onclick="switchTab('users')">Users</button>
            <button class="tab-btn" onclick="switchTab('hospitals')">Hospitals</button>
            <button class="tab-btn" onclick="switchTab('add-psychologist')">Add Psychologist</button>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboardTab" class="tab-content active">
            <div class="card">
                <h2><i class="fas fa-chart-bar"></i> System Overview</h2>
                <p>Welcome to MindCare Admin Panel. Here you can manage all aspects of the platform.</p>
            </div>
        </div>

        <!-- Psychologists Tab -->
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

        <!-- Users Tab -->
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
                            // Reset pointer since we used this result earlier
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

        <!-- Hospitals Tab -->
        <div id="hospitalsTab" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-hospital"></i> Hospital Management</h2>
                
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
            </div>
        </div>

        <!-- Add Psychologist Tab -->
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
        // Mobile Menu Toggle
        const MenuBtn = document.getElementById("MenuBtn");
        MenuBtn.onclick = () => document.body.classList.toggle("open");

        // Tab Switching
        function switchTab(tabName) {
            // Update URL hash
            window.location.hash = tabName;
            
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
            
            // Update sidebar active state
            document.querySelectorAll('.sidebar nav a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + tabName) {
                    link.classList.add('active');
                }
            });
            
            // Close mobile menu on tab switch
            if (window.innerWidth <= 1024) {
                document.body.classList.remove('open');
            }
        }

        // Handle initial tab from URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1) || 'dashboard';
            switchTab(hash);
            
            // Set default times for office hours
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

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const minFee = this.querySelector('input[name="min_fee"]');
                const maxFee = this.querySelector('input[name="max_fee"]');
                
                if (minFee && maxFee && parseFloat(minFee.value) > parseFloat(maxFee.value)) {
                    e.preventDefault();
                    alert('Minimum fee cannot be greater than maximum fee.');
                    minFee.focus();
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                }
            });
        });

        // Auto-hide alerts after 5 seconds
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