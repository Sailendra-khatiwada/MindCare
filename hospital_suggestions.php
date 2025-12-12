<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$hospitals = $conn->query("SELECT * FROM hospitals ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suggested Hospitals | MindCare</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/hospitals.css">
    
    <!-- Favicon -->
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
                <a href="hospital_suggestions.php" class="nav-link active">
                    <i class="fas fa-calendar-plus"></i>Hospital Suggestions
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-hospital-alt"></i>
                    Suggested Hospitals
                </h1>
                <p class="subtitle">Find specialized mental healthcare facilities near you</p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="hospitalSearch" placeholder="Search hospitals...">
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-h-square"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalHospitals">0</h3>
                    <p>Total Hospitals</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalLocations">0</h3>
                    <p>Locations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalSpecializations">0</h3>
                    <p>Specializations</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label for="specializationFilter">
                    <i class="fas fa-filter"></i>
                    Filter by Specialization
                </label>
                <select id="specializationFilter">
                    <option value="all">All Specializations</option>
                    <?php 
                    $specializations = $conn->query("SELECT DISTINCT specialization FROM hospitals WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization");
                    while ($spec = $specializations->fetch_assoc()): 
                    ?>
                        <option value="<?php echo htmlspecialchars($spec['specialization']); ?>">
                            <?php echo htmlspecialchars($spec['specialization']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="sortBy">
                    <i class="fas fa-sort"></i>
                    Sort By
                </label>
                <select id="sortBy">
                    <option value="name">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                    <option value="specialization">Specialization</option>
                </select>
            </div>
        </div>

        <!-- Hospitals Grid -->
        <div class="hospitals-grid" id="hospitalsContainer">
            <?php if ($hospitals->num_rows > 0): ?>
                <?php while ($row = $hospitals->fetch_assoc()): ?>
                    <div class="hospital-card" 
                         data-name="<?php echo htmlspecialchars(strtolower($row['name'])); ?>"
                         data-specialization="<?php echo htmlspecialchars(strtolower($row['specialization'])); ?>"
                         data-location="<?php echo htmlspecialchars(strtolower($row['location'])); ?>"
                         data-contact="<?php echo htmlspecialchars(strtolower($row['contact_info'])); ?>"
                         data-email="<?php echo htmlspecialchars(strtolower($row['email'])); ?>"
                         data-website="<?php echo htmlspecialchars(strtolower($row['website'])); ?>">
                         
                        <div class="hospital-header">
                            <div class="hospital-icon">
                                <i class="fas fa-hospital"></i>
                            </div>
                            <div class="hospital-title">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <div class="hospital-specialization">
                                    <i class="fas fa-stethoscope"></i>
                                    <?php echo htmlspecialchars($row['specialization']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="hospital-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <span class="detail-label">Location</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['location']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($row['contact_info'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <span class="detail-label">Contact</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['contact_info']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($row['email'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($row['email']); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($row['website'])): ?>
                            <div class="detail-item">
                                <i class="fas fa-globe"></i>
                                <div>
                                    <span class="detail-label">Website</span>
                                    <a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank" class="detail-value">
                                        Visit Website
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>  
        
                        </div>
                        
                        <div class="hospital-actions">
                            <?php if (!empty($row['location'])): ?>
                            <a href="https://maps.google.com/?q=<?php echo urlencode($row['location']); ?>" 
                               target="_blank" 
                               class="action-btn">
                                <i class="fas fa-directions"></i>
                                Directions
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-hospital-alt"></i>
                    </div>
                    <h3>No hospitals found</h3>
                    <p>There are no hospitals listed in the database yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize stats
        function updateStats() {
            const hospitals = document.querySelectorAll('.hospital-card');
            const specializations = new Set();
            const locations = new Set();
            
            hospitals.forEach(card => {
                specializations.add(card.dataset.specialization);
                locations.add(card.dataset.location);
            });
            
            document.getElementById('totalHospitals').textContent = hospitals.length;
            document.getElementById('totalLocations').textContent = locations.size;
            document.getElementById('totalSpecializations').textContent = specializations.size;
        }
        
        // Filter hospitals
        function filterHospitals() {
            const searchTerm = document.getElementById('hospitalSearch').value.toLowerCase();
            const specializationFilter = document.getElementById('specializationFilter').value.toLowerCase();
            const sortBy = document.getElementById('sortBy').value;
            
            const hospitals = Array.from(document.querySelectorAll('.hospital-card'));
            
            hospitals.forEach(card => {
                const name = card.dataset.name;
                const specialization = card.dataset.specialization;
                const location = card.dataset.location;
                
                const matchesSearch = name.includes(searchTerm) || 
                                    specialization.includes(searchTerm) || 
                                    location.includes(searchTerm);
                
                const matchesSpecialization = specializationFilter === 'all' || 
                                            specialization === specializationFilter;
                
                card.style.display = (matchesSearch && matchesSpecialization) ? 'block' : 'none';
            });
            
            // Sort hospitals
            const visibleHospitals = hospitals.filter(card => card.style.display !== 'none');
            
            visibleHospitals.sort((a, b) => {
                const aName = a.querySelector('h3').textContent;
                const bName = b.querySelector('h3').textContent;
                const aSpec = a.dataset.specialization;
                const bSpec = b.dataset.specialization;
                
                switch(sortBy) {
                    case 'name_desc':
                        return bName.localeCompare(aName);
                    case 'specialization':
                        return aSpec.localeCompare(bSpec);
                    default: // 'name'
                        return aName.localeCompare(bName);
                }
            });
            
            // Reorder in DOM
            const container = document.getElementById('hospitalsContainer');
            visibleHospitals.forEach(card => {
                container.appendChild(card);
            });
            
            updateStats();
        }
        
        // Event listeners
        document.getElementById('hospitalSearch').addEventListener('input', filterHospitals);
        document.getElementById('specializationFilter').addEventListener('change', filterHospitals);
        document.getElementById('sortBy').addEventListener('change', filterHospitals);
        
        // Initial stats update
        document.addEventListener('DOMContentLoaded', updateStats);
        
        // Add animation to cards on load
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.hospital-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.01}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>