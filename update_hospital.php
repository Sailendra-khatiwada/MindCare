<?php
session_start();
include 'db_connect.php';

// Ensure the admin is logged in
if (!isset($_SESSION['username']) || $_SESSION['username'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Initialize variables
$hospital = [];
$errors = [];
$success = false;

// Get hospital ID from URL with validation
if (isset($_GET['hospital_id']) && is_numeric($_GET['hospital_id'])) {
    $hospital_id = intval($_GET['hospital_id']);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();
    $stmt->close();

    if (!$hospital) {
        header('Location: admin_dashboard.php');
        exit;
    }
} else {
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $name = trim(filter_input(INPUT_POST, 'name'));
    $location = trim(filter_input(INPUT_POST, 'location'));
    $specialization = trim(filter_input(INPUT_POST, 'specialization', FILTER_SANITIZE_STRING));
    $contact = trim(filter_input(INPUT_POST, 'contact'));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $website = trim(filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL));

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Hospital name is required';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Hospital name must be at least 3 characters';
    }

    if (empty($location)) {
        $errors['location'] = 'Location is required';
    }

    if (empty($specialization)) {
        $errors['specialization'] = 'Specialization is required';
    }

    if (empty($contact)) {
        $errors['contact_info'] = 'Contact number is required';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $contact)) {
        $errors['contact_info'] = 'Please enter a valid contact number';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Please enter a valid website URL';
    }

    // If no errors, update database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE hospitals SET name=?, location=?, specialization=?, contact_info=?, email=?, website=? WHERE hospital_id=?");
        $stmt->bind_param("ssssssi", $name, $location, $specialization, $contact, $email, $website, $hospital_id);

        if ($stmt->execute()) {
            $success = true;
            // Update local hospital data for form
            $hospital = array_merge($hospital, [
                'name' => $name,
                'location' => $location,
                'specialization' => $specialization,
                'contact_info' => $contact,
                'email' => $email,
                'website' => $website
            ]);
        } else {
            $errors['database'] = 'Error updating hospital: ' . $conn->error;
        }
        $stmt->close();
    }
}

function getFormValue($field, $default = '')
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        return $_POST[$field] ?? $default;
    }
    return $default;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Hospital | MindCare Admin</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="css/update_hospital.css">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏥</text></svg>">
</head>

<body>
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="brand">
                <span class="brand-mark" aria-hidden="true">🕊️</span>
                <span class="brand-text">MindCare Admin</span>
            </a>
            <div class="nav-links">
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-hospital-alt"></i>
                    Update Hospital
                </h1>
                <p class="subtitle">Edit hospital information and details</p>
            </div>
            <div class="header-actions">
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <div class="alert-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="alert-content">
                    <h4>Success!</h4>
                    <p>Hospital information has been updated successfully.</p>
                </div>
                <a href="admin_dashboard.php" class="alert-action">
                    View Dashboard
                </a>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors['database'])): ?>
            <div class="alert alert-error">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-content">
                    <h4>Database Error</h4>
                    <p><?php echo htmlspecialchars($errors['database']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Update Form -->
        <div class="update-form-container">
            <form id="updateHospitalForm" class="update-form" method="POST" novalidate>
                <!-- Basic Information Card -->
                <div class="form-card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <!-- Hospital Name -->
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-hospital"></i>
                                    Hospital Name *
                                </label>
                                <input type="text"
                                    id="name"
                                    name="name"
                                    class="form-input <?php echo isset($errors['name']) ? 'error' : ''; ?>"
                                    value="<?php echo htmlspecialchars($hospital['name'] ?? ''); ?>"

                                    placeholder="Enter hospital name">
                                <?php if (isset($errors['name'])): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Location -->
                            <div class="form-group">
                                <label for="location" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Location *
                                </label>
                                <input type="text"
                                    id="location"
                                    name="location"
                                    class="form-input <?php echo isset($errors['location']) ? 'error' : ''; ?>"
                                    value="<?php echo htmlspecialchars($hospital['location'] ?? ''); ?>"

                                    placeholder="Enter hospital location">
                                <?php if (isset($errors['location'])): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['location']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Contact Field - Updated -->
                            <div class="form-group">
                                <label for="contact" class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Contact Number *
                                </label>
                                <input type="tel"
                                    id="contact"
                                    name="contact"
                                    class="form-input <?php echo isset($errors['contact_info']) ? 'error' : ''; ?>"
                                    value="<?php
                                            echo htmlspecialchars(
                                                $_SERVER['REQUEST_METHOD'] == 'POST'
                                                    ? ($_POST['contact'] ?? $hospital['contact_info'] ?? '')
                                                    : ($hospital['contact_info'] ?? '')
                                            );
                                            ?>"
                                    placeholder="e.g., +1 (555) 123-4567">
                                <?php if (isset($errors['contact_info'])): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['contact_info']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Specialization Field - Updated -->
                            <div class="form-group">
                                <label for="specialization" class="form-label">
                                    <i class="fas fa-stethoscope"></i>
                                    Specialization *
                                </label>
                                <select id="specialization"
                                    name="specialization"
                                    class="form-select <?php echo isset($errors['specialization']) ? 'error' : ''; ?>">
                                    <?php
                                    $currentSpecialization = '';
                                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                        $currentSpecialization = $_POST['specialization'] ?? '';
                                    } else {
                                        $currentSpecialization = $hospital['specialization'] ?? '';
                                    }

                                    $specializations = [
                                        'Psychiatry',
                                        'Psychology',
                                        'Counseling',
                                        'Rehabilitation',
                                        'Mental Health',
                                        'Psychotherapy'
                                    ];
                                    ?>

                                    <option value="">Select Specialization</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo $spec; ?>"
                                            <?php echo $currentSpecialization == $spec ? 'selected' : ''; ?>>
                                            <?php echo $spec; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['specialization'])): ?>
                                    <div class="form-error">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['specialization']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Contact Information Card -->
                    <div class="form-card">
                        <div class="card-header">
                            <h3>
                                <i class="fas fa-address-book"></i>
                                Contact Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-grid">
                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <input type="email"
                                        id="email"
                                        name="email"
                                        class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                                        value="<?php echo htmlspecialchars($hospital['email'] ?? ''); ?>"
                                        placeholder="hospital@example.com">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="form-error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Website -->
                                <div class="form-group">
                                    <label for="website" class="form-label">
                                        <i class="fas fa-globe"></i>
                                        Website
                                    </label>
                                    <input type="url"
                                        id="website"
                                        name="website"
                                        class="form-input <?php echo isset($errors['website']) ? 'error' : ''; ?>"
                                        value="<?php echo htmlspecialchars($hospital['website'] ?? ''); ?>"
                                        placeholder="https://example.com">
                                    <?php if (isset($errors['website'])): ?>
                                        <div class="form-error">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($errors['website']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Hospital
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <script>
        // Show error for specific field
        function showFieldError(field, message) {
            field.classList.add('error');

            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;

            field.parentElement.appendChild(errorDiv);

            // Scroll to error
            errorDiv.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        };

        // Show general form error
        function showFormError(message) {
            const existingAlert = document.querySelector('.alert-error');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.innerHTML = `
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <h4>Form Error</h4>
                        <p>${message}</p>
                    </div>
                `;

            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.children[1]);

            // Scroll to alert
            setTimeout(() => {
                alertDiv.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);
        };

        // Smooth scroll for form cards
        document.querySelectorAll('.form-card .card-header').forEach(header => {
            header.addEventListener('click', function() {
                const card = this.parentElement;
                card.classList.toggle('expanded');
            });
        });
    </script>
</body>

</html>