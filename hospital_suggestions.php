<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

include 'db_connect.php';

$hospitals = $conn->query("SELECT * FROM hospitals");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Suggested Hospitals</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            background: #fff;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        ul li strong {
            font-size: 16px;
        }
    </style>
</head>

<body>
    <h1>Suggested Hospitals</h1>
    <ul>
        <?php while ($row = $hospitals->fetch_assoc()): ?>
            <li><?php echo $row['name']; ?> - <?php echo $row['specialization']; ?><br><?php echo $row['location']; ?></li>
        <?php endwhile; ?>
    </ul>

</body>

</html>