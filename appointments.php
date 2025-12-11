<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'db_connect.php';

$psychologists = $conn->query("
    SELECT p_id, username, profile_picture, specialization, location, education,
           min_fee, max_fee, office_start, office_end, contact_info
    FROM psychologist
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | MindCare</title>
    <link rel="stylesheet" href="css/appointment.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🕊️</text></svg>">
</head>

<body>
    <header class="page-header">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark" aria-hidden="true">🕊️</span>
            <span class="brand-text">MindCare</span>
        </a>
        <div class="user-nav">
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="appointments.php" class="nav-link active">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </a>
            </div>
        </div>
    </header>
    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-calendar-plus"></i>
            Book an Appointment
        </h1>

        <p class="page-subtitle">
            Connect with licensed professionals who can help you on your mental wellness journey
        </p>
        <div class="search-filter-bar">
            <div class="search-container">
                <input type="text"
                    id="search-location"
                    class="search-input"
                    placeholder="Search by city, state, or zip code..."
                    onkeyup="filterLocation()">

                <button class="search-btn" onclick="filterLocation()">
                    <i class="fas fa-search"></i>
                    Search
                </button>
            </div>
            <div class="filter-options">
                <a href="#" id="filter-All" class="filter-option active" onclick="filterSpecialization('All')">
                    All Specializations
                </a>
                <a href="#" id="filter-Psychologist" class="filter-option" onclick="filterSpecialization('Psychologist')">
                    <i class="fas fa-brain"></i> Psychologist
                </a>
                <a href="#" id="filter-Psychiatrist" class="filter-option" onclick="filterSpecialization('Psychiatrist')">
                    <i class="fas fa-user-md"></i> Psychiatrist
                </a>
                <a href="#" id="filter-Clinical-Psychologist" class="filter-option" onclick="filterSpecialization('Clinical Psychologist')">
                    <i class="fas fa-stethoscope"></i> Clinical Psychologist
                </a>
                <a href="#" id="filter-Psychotherapist" class="filter-option" onclick="filterSpecialization('Psychotherapist')">
                    <i class="fas fa-comments"></i> Psychotherapist
                </a>
                <a href="#" id="filter-Counseling-Psychologist" class="filter-option" onclick="filterSpecialization('Counseling Psychologist')">
                    <i class="fas fa-hands-helping"></i> Counseling Psychologist
                </a>
                <a href="#" id="filter-Therapist" class="filter-option" onclick="filterSpecialization('Therapist')">
                    <i class="fas fa-heart"></i> Therapist
                </a>
            </div>
        </div>

        <div class="psychologist-grid" id="psychologistGrid">
            <?php if ($psychologists && $psychologists->num_rows > 0): ?>
                <?php while ($row = $psychologists->fetch_assoc()): ?>
                    <div class="psychologist-card" data-specialization="<?php echo htmlspecialchars($row['specialization']); ?>" data-location="<?php echo htmlspecialchars(strtolower($row['location'])); ?>">

                        <div class="card-header">
                            <div class="profile-section">
                                <img src="<?php echo !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'images/default-profile.jpg'; ?>"
                                    alt="<?php echo htmlspecialchars($row['username']); ?>"
                                    class="profile-image">
                                <div class="profile-info">
                                    <h3 class="profile-name">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                        <span class="verified-badge" title="Verified Professional">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    </h3>
                                    <p class="specialization"><?php echo htmlspecialchars($row['specialization']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Location</div>
                                    <div class="detail-value location"><?php echo htmlspecialchars($row['location']); ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Education</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['education']); ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Office Hours</div>
                                    <div class="detail-value">
                                        <?php echo date('g:i A', strtotime($row['office_start'])) . " - " . date('g:i A', strtotime($row['office_end'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="fees">
                                <div class="detail-label">Session Fee</div>
                                <div class="fee-amount">
                                    Rs. <?php echo number_format($row['min_fee']); ?> - <?php echo number_format($row['max_fee']); ?>
                                </div>
                                <div class="fee-period">per 50-minute session</div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="psychologist_details.php?id=<?php echo $row['p_id']; ?>" class="action-btn btn-outline">
                                <i class="fas fa-eye"></i>
                                View Profile
                            </a>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h2 class="empty-title">No Psychologists Available</h2>
                    <p class="empty-text">
                        There are currently no psychologists available in our network.
                        Please check back later or contact support for assistance.
                    </p>
                    <a href="dashboard.php" class="action-btn btn-primary" style="width: auto; display: inline-flex;">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <script>
            function filterLocation() {
                let input = document.getElementById('search-location').value.toLowerCase();
                let cards = document.querySelectorAll('.psychologist-card');
                cards.forEach(card => {
                    let location = card.querySelector('.location').innerText.toLowerCase();
                    let matches = location.includes(input);
                    if (matches) {
                        card.style.display = 'flex';
                        card.style.animation = 'fadeIn 0.5s ease-out';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            function filterSpecialization(specialization) {
                let cards = document.querySelectorAll('.psychologist-card');
                cards.forEach(card => {
                    let sp = card.getAttribute('data-specialization').toLowerCase();
                    let matches = specialization === 'All' || sp.includes(specialization.toLowerCase());
                    if (matches) {
                        card.style.display = 'flex';
                        card.style.animation = 'fadeIn 0.5s ease-out';
                    } else {
                        card.style.display = 'none';
                    }
                });
                document.querySelectorAll('.filter-option').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.getElementById(`filter-${specialization.replace(/\s+/g, '-')}`).classList.add('active');
                document.getElementById('search-location').value = '';
            }
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('search-location');
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        filterLocation();
                        const firstVisible = document.querySelector('.psychologist-card[style*="flex"]');
                        if (firstVisible) {
                            firstVisible.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    }
                });
                document.querySelectorAll('.filter-option').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    });
                });
            });
        </script>
    </body>
</html>


