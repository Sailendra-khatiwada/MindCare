<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$user_id = $_SESSION['user_id'];

/* Handle Form Submit */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $medication_name = $_POST['medication_name'];
    $dosage = $_POST['dosage'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("INSERT INTO medications (user_id, medication_name, dosage, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $medication_name, $dosage, $start_date, $end_date);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Medication added successfully!";
        header('Location: medications.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

/* Fetch medication list */
$sql = "SELECT * FROM medications WHERE user_id = $user_id ORDER BY start_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Medications</title>
    <link rel="stylesheet" href="css/medications.css">
</head>

<body>

    <div class="container">

        <h1 class="page-title">💊 Medication Manager</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <div class="main-grid">

            <!-- LEFT: Add Form -->
            <div class="card form-card">
                <h2>Add Medication</h2>

                <form method="POST">

                    <div class="input-group">
                        <label>Medication Name</label>
                        <input type="text" name="medication_name" required>
                    </div>

                    <div class="input-group">
                        <label>Dosage</label>
                        <input type="text" name="dosage" required>
                    </div>

                    <div class="input-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required>
                    </div>

                    <div class="input-group">
                        <label>End Date</label>
                        <input type="date" name="end_date">
                    </div>

                    <button type="submit" class="save-btn">Save Medication</button>
                    <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
                </form>
            </div>

            <!-- RIGHT: Medication List -->
            <div class="card list-card">
                <h2>Medication History</h2>

                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Dosage</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['medication_name']) ?></td>
                                    <td><?= htmlspecialchars($row['dosage']) ?></td>
                                    <td><?= $row['start_date'] ?></td>
                                    <td><?= $row['end_date'] ?: "—" ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">No medications added yet.</p>
                <?php endif; ?>

            </div>

        </div>

    </div>

</body>
</html>
