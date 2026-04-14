<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
  SELECT 
    a.appointment_id, 
    p.p_id, 
    p.username,
    (SELECT COUNT(*) FROM messages m 
     WHERE m.appointment_id = a.appointment_id 
     AND m.sender_type = 'psychologist' 
     AND m.seen = 0) as unread_count
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

// Calculate total unread
$total_unread = 0;
$conversations = [];
while ($row = $res->fetch_assoc()) {
  $conversations[] = $row;
  $total_unread += $row['unread_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | MindCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="header">
    <div class="header-content">
      <a href="../dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </a>
      <div class="header-title">
        <i class="fas fa-comments"></i>
        My Messages
      </div>
    </div>

    <div class="message-stats">
      <div class="stat-item">
        <i class="fas fa-user-md"></i>
        <span class="stat-number"><?php echo count($conversations); ?> Therapists</span>
      </div>
      <div class="stat-item">
        <i class="fas fa-clock"></i>
        <span>Available 24/7</span>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="welcome-card">
      <div class="welcome-icon">
        <i class="fas fa-comment-dots"></i>
      </div>
      <h1 class="welcome-title">Your Therapy Conversations</h1>
      <p class="welcome-text">
        Connect with your therapists in a safe, confidential space.
        Your messages are encrypted and protected by professional standards.
      </p>
    </div>

    <div class="chat-list">
      <div class="chat-header">
        <h2>
          <i class="fas fa-user-md"></i>
          Active Conversations
          <span class="unread-count" id="totalUnread" style="background: <?php echo $total_unread > 0 ? 'var(--accent)' : 'var(--success)'; ?>">
            <?php echo $total_unread > 0 ? $total_unread . ' New' : 'All Read'; ?>
          </span>
        </h2>
        <div class="chat-info">
          <small style="color: rgba(255,255,255,0.8);">
            <i class="fas fa-lock"></i> End-to-end encrypted
          </small>
        </div>
      </div>

      <?php if (count($conversations) > 0): ?>
        <?php foreach ($conversations as $index => $row): ?>
          <?php
          $initial = strtoupper(substr($row['username'], 0, 1));
          $hasUnread = $row['unread_count'] > 0;
          $newMessages = $row['unread_count'];
          ?>

          <a href="user_chat.php?appointment_id=<?php echo $row['appointment_id']; ?>"
            class="chat-item <?php echo $hasUnread ? 'new' : ''; ?>"
            style="text-decoration: none; color: inherit;">

            <div class="chat-avatar">
              <div class="avatar-circle">
                <?php echo $initial; ?>
              </div>
            </div>

            <div class="chat-content">
              <div class="chat-name">
                <?php echo htmlspecialchars($row['username']); ?>
                <span class="badge">Licensed Therapist</span>
              </div>

              <div class="chat-preview">
                <?php echo $hasUnread ? "You have " . $newMessages . " new message" . ($newMessages > 1 ? "s" : "") : "Click to open conversation"; ?>
              </div>

              <?php if ($hasUnread): ?>
                <div class="chat-time" style="color: var(--accent);">
                  <i class="fas fa-envelope"></i> <?php echo $newMessages; ?> unread
                </div>
              <?php endif; ?>
            </div>

            <div class="chat-action">
              <span class="open-chat-btn">
                <?php echo $hasUnread ? 'Read Messages' : 'Open Chat'; ?>
                <i class="fas fa-arrow-right"></i>
              </span>
            </div>
          </a>

        <?php endforeach; ?>

      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-comment-slash"></i>
          </div>
          <h2 class="empty-title">No Active Conversations</h2>
          <p class="empty-text">
            You currently don't have any approved appointments with therapists.
            Once your appointment is approved by the therapist, you'll be able to
            start messaging here.
          </p>

          <div class="action-buttons">
            <a href="../appointments.php" class="action-btn btn-primary">
              <i class="fas fa-calendar-plus"></i>
              Book Appointment
            </a>
            <a href="../dashboard.php" class="action-btn btn-outline">
              <i class="fas fa-home"></i>
              Go to Dashboard
            </a>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <div class="footer">
      <p>
        <i class="fas fa-shield-alt"></i>
        All conversations are confidential and protected by HIPAA standards
      </p>
    </div>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const chatItems = document.querySelectorAll('.chat-item');
      chatItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.style.animation = 'slideIn 0.5s ease-out forwards';
        item.style.opacity = '0';
      });

      chatItems.forEach(item => {
        item.addEventListener('click', function(e) {
          const ripple = document.createElement('span');
          const rect = this.getBoundingClientRect();
          const size = Math.max(rect.width, rect.height);
          const x = e.clientX - rect.left - size / 2;
          const y = e.clientY - rect.top - size / 2;

          ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(74, 123, 157, 0.2);
            transform: scale(0);
            animation: ripple 0.6s linear;
            width: ${size}px;
            height: ${size}px;
            top: ${y}px;
            left: ${x}px;
            pointer-events: none;
          `;

          this.style.position = 'relative';
          this.style.overflow = 'hidden';
          this.appendChild(ripple);

          setTimeout(() => {
            ripple.remove();
          }, 600);
        });
      });

      const style = document.createElement('style');
      style.textContent = `
        @keyframes ripple {
          to {
            transform: scale(4);
            opacity: 0;
          }
        }
        
        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateX(-20px);
          }
          to {
            opacity: 1;
            transform: translateX(0);
          }
        }
        
        .chat-item.new {
          background: linear-gradient(135deg, rgba(74, 123, 157, 0.08) 0%, rgba(255, 255, 255, 0.95) 100%);
          border-left: 4px solid var(--accent);
        }
      `;
      document.head.appendChild(style);
    });

    // Real-time unread check every 30 seconds
    setInterval(() => {
      fetch('check_unread.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const unreadBadge = document.getElementById('totalUnread');
            if (data.total_unread > 0) {
              unreadBadge.textContent = `${data.total_unread} New`;
              unreadBadge.style.background = 'var(--accent)';
            } else {
              unreadBadge.textContent = 'All Read';
              unreadBadge.style.background = 'var(--success)';
            }

            // Update individual conversation unread counts
            if (data.conversations) {
              data.conversations.forEach(conv => {
                const chatItem = document.querySelector(`a[href*="appointment_id=${conv.appointment_id}"]`);
                if (chatItem) {
                  const preview = chatItem.querySelector('.chat-preview');
                  const chatTime = chatItem.querySelector('.chat-time');

                  if (conv.unread_count > 0) {
                    chatItem.classList.add('new');
                    preview.textContent = `You have ${conv.unread_count} new message${conv.unread_count > 1 ? 's' : ''}`;

                    if (!chatTime) {
                      const newChatTime = document.createElement('div');
                      newChatTime.className = 'chat-time';
                      newChatTime.style.color = 'var(--accent)';
                      newChatTime.innerHTML = `<i class="fas fa-envelope"></i> ${conv.unread_count} unread`;
                      chatItem.querySelector('.chat-content').appendChild(newChatTime);
                    } else {
                      chatTime.innerHTML = `<i class="fas fa-envelope"></i> ${conv.unread_count} unread`;
                    }

                    const openBtn = chatItem.querySelector('.open-chat-btn');
                    openBtn.innerHTML = 'Read Messages <i class="fas fa-arrow-right"></i>';
                  } else {
                    chatItem.classList.remove('new');
                    preview.textContent = 'Click to open conversation';

                    if (chatTime) {
                      chatTime.remove();
                    }

                    const openBtn = chatItem.querySelector('.open-chat-btn');
                    openBtn.innerHTML = 'Open Chat <i class="fas fa-arrow-right"></i>';
                  }
                }
              });
            }
          }
        })
        .catch(error => console.error('Error:', error));
    }, 30000);
  </script>
</body>

</html>