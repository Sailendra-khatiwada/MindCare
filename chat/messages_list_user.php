<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
  SELECT a.appointment_id, p.p_id, p.username
  FROM appointments a
  JOIN psychologist p ON a.p_id = p.p_id
  WHERE a.user_id = ? AND a.status = 'approved'
  ORDER BY a.appointment_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("SQL prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Messages</title>
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f2f4f7;
    }

    .header {
      background: #4a6cf7;
      color: white;
      padding: 18px 25px;
      font-size: 22px;
      font-weight: bold;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .back-btn {
      text-decoration: none;
      color: white;
      font-size: 16px;
      background: rgba(255, 255, 255, 0.25);
      padding: 6px 12px;
      border-radius: 6px;
      margin-right: 15px;
    }

    .back-btn:hover {
      background: rgba(255, 255, 255, 0.35);
    }


    .container {
      max-width: 520px;
      margin: 30px auto;
      background: white;
      padding: 0;
      border-radius: 14px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .chat-item {
      display: flex;
      align-items: center;
      padding: 18px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      transition: 0.15s ease;
    }

    .chat-item:hover {
      background: #f7f9ff;
      transform: translateX(4px);
    }

    .avatar {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      background: #dfe3ff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      color: #4a6cf7;
      margin-right: 15px;
    }

    .chat-info {
      flex: 1;
    }

    .name {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .subtext {
      font-size: 14px;
      color: #777;
    }

    .open-btn {
      background: #4a6cf7;
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      transition: 0.2s;
    }

    .open-btn:hover {
      background: #394fcc;
    }

    .empty {
      padding: 25px;
      text-align: center;
      font-size: 16px;
      color: #666;
    }
  </style>
</head>

<body>

  <div class="header">
    <a href="../dashboard.php" class="back-btn">⬅ Back</a>
    My Messages
  </div>

  <div class="container">

    <?php if ($res->num_rows > 0): ?>
      <?php while ($row = $res->fetch_assoc()): ?>
        <?php $initial = strtoupper(substr($row['username'], 0, 1)); ?>

        <a href="user_chat.php?appointment_id=<?php echo $row['appointment_id']; ?>" style="text-decoration:none; color:inherit;">
          <div class="chat-item">

            <div class="avatar"><?php echo $initial; ?></div>

            <div class="chat-info">
              <div class="name"><?php echo htmlspecialchars($row['username']); ?></div>
              <div class="subtext">Tap to open chat</div>
            </div>

          </div>
        </a>

      <?php endwhile; ?>

    <?php else: ?>
      <div class="empty">You have no approved appointments yet or Chat is Locked(Waiting for approved).</div>
    <?php endif; ?>

  </div>

</body>

</html>