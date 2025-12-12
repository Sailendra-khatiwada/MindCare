<?php
include 'db_connect.php';

if (isset($_GET['hospital_id'])) {
    $id = intval($_GET['hospital_id']);

    $stmt = $conn->prepare("DELETE FROM hospitals WHERE hospital_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Hospital removed successfully');</script>";
        header("Location: admin_dashboard.php#hospitals");
        exit;
    } else {

        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
