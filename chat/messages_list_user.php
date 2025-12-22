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
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | MindCare</title>
  
  <!-- Styles -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- Header -->
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
        <span class="stat-number"><?php echo $res->num_rows; ?> Therapists</span>
      </div>
      <div class="stat-item">
        <i class="fas fa-clock"></i>
        <span>Available 24/7</span>
      </div>
    </div>
  </div>

  <!-- Main Container -->
  <div class="container">

    <!-- Welcome Card -->
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

    <!-- Chat List -->
    <div class="chat-list">
      <div class="chat-header">
        <h2>
          <i class="fas fa-user-md"></i>
          Active Conversations
          <span class="unread-count" id="totalUnread">0 New</span>
        </h2>
        <div class="chat-info">
          <small style="color: rgba(255,255,255,0.8);">
            <i class="fas fa-lock"></i> End-to-end encrypted
          </small>
        </div>
      </div>

      <?php if ($res->num_rows > 0): ?>
        <?php while ($row = $res->fetch_assoc()): ?>
          <?php 
            $initial = strtoupper(substr($row['username'], 0, 1));
            // Simulate some chat data (in real app, you would fetch from database)
            $lastMessage = "Continue our discussion from last session...";
            $lastTime = "2 hours ago";
            $isOnline = rand(0, 1);
            $hasUnread = rand(0, 1);
            $newMessages = rand(0, 3);
          ?>
          
          <a href="user_chat.php?appointment_id=<?php echo $row['appointment_id']; ?>" 
             class="chat-item <?php echo $hasUnread ? 'new' : ''; ?>"
             style="text-decoration: none; color: inherit;">
            
            <div class="chat-avatar">
              <div class="avatar-circle">
                <?php echo $initial; ?>
              </div>
              <?php if ($isOnline): ?>
                <span class="online-indicator" title="Online now"></span>
              <?php endif; ?>
            </div>

            <div class="chat-content">
              <div class="chat-name">
                <?php echo htmlspecialchars($row['username']); ?>
                <span class="badge">Licensed Therapist</span>
              </div>
              
              <div class="chat-preview">
                <?php echo $lastMessage; ?>
              </div>
              
              <div class="chat-time">
                <i class="far fa-clock"></i>
                <?php echo $lastTime; ?>
                <?php if ($hasUnread && $newMessages > 0): ?>
                  <span style="color: var(--accent); font-weight: 600; margin-left: 1rem;">
                    <i class="fas fa-envelope"></i> <?php echo $newMessages; ?> new
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="chat-action">
              <span class="open-chat-btn">
                Open Chat
                <i class="fas fa-arrow-right"></i>
              </span>
            </div>
          </a>

        <?php endwhile; ?>

      <?php else: ?>
        <!-- Empty State -->
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

    <!-- Footer -->
    <div class="footer">
      <p>
        <i class="fas fa-shield-alt"></i> 
        All conversations are confidential and protected by HIPAA standards
      </p>
    </div>

  </div>

  <script>
    // Update unread count dynamically (you would fetch this from your server)
    function updateUnreadCount() {
      // This is a simulation - in real app, fetch from your PHP endpoint
      const chatItems = document.querySelectorAll('.chat-item');
      let totalUnread = 0;
      
      chatItems.forEach(item => {
        if (item.classList.contains('new')) {
          totalUnread++;
        }
      });
      
      const unreadBadge = document.getElementById('totalUnread');
      if (unreadBadge) {
        if (totalUnread > 0) {
          unreadBadge.textContent = `${totalUnread} New`;
          unreadBadge.style.background = 'var(--accent)';
        } else {
          unreadBadge.textContent = 'All Read';
          unreadBadge.style.background = 'var(--success)';
        }
      }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      updateUnreadCount();
      
      // Add animations to chat items
      const chatItems = document.querySelectorAll('.chat-item');
      chatItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.style.animation = 'slideIn 0.5s ease-out forwards';
      });

      // Add click animation to chat items
      chatItems.forEach(item => {
        item.addEventListener('click', function(e) {
          // Add ripple effect
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

      // Add ripple animation
      const style = document.createElement('style');
      style.textContent = `
        @keyframes ripple {
          to {
            transform: scale(4);
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);
    });

    // Check for new messages periodically (every 30 seconds)
    setInterval(() => {
      // In a real app, you would make an AJAX call here
      // For now, we'll just simulate some random updates
      const shouldUpdate = Math.random() > 0.8;
      if (shouldUpdate) {
        updateUnreadCount();
      }
    }, 30000);
  </script>
</body>
</html>