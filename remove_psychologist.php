<?php
include 'db_connect.php';

if (isset($_GET['p_id'])) {
    $id = $_GET['p_id'];

    $stmt = $conn->prepare("DELETE FROM psychologist WHERE p_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Psychologist removed successfully');</script>";
        header('Location: admin_dashboard.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
