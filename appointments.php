<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'db_connect.php';

// Fetch Psychologists
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
    <title>Book an Appointment</title>
    <link rel="stylesheet" href="appointment.css">

    <script>
        // Filter by Location
        function filterLocation() {
            let input = document.getElementById('search-location').value.toLowerCase();
            let cards = document.getElementsByClassName('card');

            for (let card of cards) {
                let location = card.querySelector('.card-details .location').innerText.toLowerCase();
                card.style.display = location.includes(input) ? 'block' : 'none';
            }
        }

        // Filter by Specialization
        function filterSpecialization(specialization) {
            let cards = document.getElementsByClassName('card');

            for (let card of cards) {
                let sp = card.querySelector('.specialization').innerText.toLowerCase();
                card.style.display =
                    specialization === 'All' || sp.includes(specialization.toLowerCase())
                        ? 'block'
                        : 'none';
            }

            document.querySelectorAll('.filter-options a').forEach(a => a.classList.remove('active'));
            document.getElementById(`filter-${specialization.replace(/\s+/g, '-')}`).classList.add('active');
        }
    </script>
</head>

<body>

    <h2>Book an Appointment</h2>

    <!-- Filters -->
    <div class="filter-options">
        <a href="#" id="filter-All" onclick="filterSpecialization('All')">All</a>
        <a href="#" id="filter-Psychologist" onclick="filterSpecialization('Psychologist')">Psychologist</a>
        <a href="#" id="filter-Psychiatrist" onclick="filterSpecialization('Psychiatrist')">Psychiatrist</a>
        <a href="#" id="filter-Clinical-Psychologist" onclick="filterSpecialization('Clinical Psychologist')">Clinical Psychologist</a>
        <a href="#" id="filter-Psychotherapist" onclick="filterSpecialization('Psychotherapist')">Psychotherapist</a>
        <a href="#" id="filter-Counseling-Psychologist" onclick="filterSpecialization('Counseling Psychologist')">Counseling Psychologist</a>
        <a href="#" id="filter-Therapist" onclick="filterSpecialization('Therapist')">Therapist</a>
    </div>

    <!-- Location Search -->
    <input type="text" id="search-location" placeholder="Search by Location" onkeyup="filterLocation()">

    <!-- Psychologist List -->
    <div class="psychologist-cards">
        <?php while ($row = $psychologists->fetch_assoc()): ?>
            <div class="card">

                <div class="card-header">
                    <img src="<?php echo $row['profile_picture'] ?: 'images/default-profile.jpg'; ?>"
                        alt="Psychologist Photo" class="profile-pic">

                    <div class="card-title">
                        <h3>
                            <?php echo htmlspecialchars($row['username']); ?>
                            <span class="verified">&#10004;</span>
                        </h3>
                        <p class="specialization"><?php echo htmlspecialchars($row['specialization']); ?></p>
                    </div>
                </div>

                <div class="card-details">
                    <p class="location"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                    <p><strong>Education:</strong> <?php echo htmlspecialchars($row['education']); ?></p>
                    <p><strong>Office Hours:</strong>
                        <?php echo date('g:i A', strtotime($row['office_start'])) . " - " . date('g:i A', strtotime($row['office_end'])); ?>
                    </p>
                    <p><strong>Fees:</strong> Rs. <?php echo $row['min_fee']; ?> - <?php echo $row['max_fee']; ?></p>
                </div>

                <!-- View More Button -->
                <a href="psychologist_details.php?id=<?php echo $row['p_id']; ?>" class="view-more-btn">
                    View Details
                </a>

            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>


