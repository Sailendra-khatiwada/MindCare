<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query("
    SELECT admin_id, messages 
    FROM admin 
    WHERE messages IS NOT NULL 
    ORDER BY admin_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Messages | MindCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Inter, Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
            color: #1f2937;
        }

        .message-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            white-space: pre-wrap;
        }

        .empty {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            color: #6b7280;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn {
            padding: 8px 14px;
            background: #6a9c89;
            ;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn:hover {
            background: #63897bff;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="top-bar">
            <h1>📩 Contact Messages</h1>
            <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
        </div>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="message-card">
                    <?= nl2br(htmlspecialchars($row['messages'])) ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty">
                No messages found.
            </div>
        <?php endif; ?>
    </div>

</body>
</html>