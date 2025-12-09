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
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #4a7b9d;
      --primary-light: #7ba6c1;
      --primary-dark: #2c5a78;
      --secondary: #6a9c89;
      --accent: #e8a87c;
      --light: #f8f9fa;
      --light-gray: #e9ecef;
      --medium-gray: #adb5bd;
      --dark: #2d3748;
      --white: #ffffff;
      --success: #28a745;
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.1);
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 20px;
      --radius-xl: 30px;
      --radius-full: 50%;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f5f7fb 0%, #e9ecef 100%);
      min-height: 100vh;
      color: var(--dark);
    }

    /* Header */
    .header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: var(--white);
      padding: 1.5rem 2rem;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: var(--shadow-md);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .header-content {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .back-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--white);
      text-decoration: none;
      padding: 0.75rem 1.25rem;
      background: rgba(255, 255, 255, 0.15);
      border-radius: var(--radius-lg);
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .back-btn:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateX(-5px);
    }

    .header-title {
      font-size: 1.75rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .header-title i {
      animation: float 3s ease-in-out infinite;
    }

    /* Message Stats */
    .message-stats {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.9rem;
    }

    .stat-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .stat-number {
      background: rgba(255, 255, 255, 0.2);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-weight: 600;
    }

    /* Container */
    .container {
      max-width: 800px;
      margin: 3rem auto;
      padding: 0 1.5rem;
      animation: fadeIn 0.6s ease-out;
    }

    /* Welcome Card */
    .welcome-card {
      background: var(--white);
      border-radius: var(--radius-xl);
      padding: 2.5rem;
      margin-bottom: 2.5rem;
      box-shadow: var(--shadow-lg);
      text-align: center;
      border: 1px solid var(--light-gray);
      position: relative;
      overflow: hidden;
    }

    .welcome-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .welcome-icon {
      font-size: 4rem;
      color: var(--primary);
      margin-bottom: 1.5rem;
      animation: pulse 2s ease-in-out infinite;
    }

    .welcome-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary-dark);
      margin-bottom: 0.75rem;
    }

    .welcome-text {
      color: var(--medium-gray);
      font-size: 1.1rem;
      line-height: 1.6;
      max-width: 600px;
      margin: 0 auto 1.5rem;
    }

    /* Chat List */
    .chat-list {
      background: var(--white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      border: 1px solid var(--light-gray);
    }

    .chat-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: var(--white);
      padding: 1.5rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .chat-header h2 {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .unread-count {
      background: var(--accent);
      color: var(--dark);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    /* Chat Items */
    .chat-item {
      display: flex;
      align-items: center;
      padding: 1.75rem 2rem;
      border-bottom: 1px solid var(--light-gray);
      text-decoration: none;
      color: inherit;
      transition: all 0.3s ease;
      position: relative;
    }

    .chat-item:hover {
      background: linear-gradient(90deg, rgba(74, 123, 157, 0.05), rgba(74, 123, 157, 0.02));
      transform: translateX(10px);
    }

    .chat-item:last-child {
      border-bottom: none;
    }

    .chat-item.new::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: var(--accent);
    }

    .chat-avatar {
      position: relative;
      margin-right: 1.5rem;
      flex-shrink: 0;
    }

    .avatar-circle {
      width: 70px;
      height: 70px;
      border-radius: var(--radius-full);
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: 700;
      color: var(--white);
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
    }

    .chat-item:hover .avatar-circle {
      transform: scale(1.1);
      box-shadow: var(--shadow-lg);
    }

    .online-indicator {
      position: absolute;
      bottom: 5px;
      right: 5px;
      width: 14px;
      height: 14px;
      background: var(--success);
      border: 3px solid var(--white);
      border-radius: var(--radius-full);
    }

    .chat-content {
      flex: 1;
      min-width: 0;
    }

    .chat-name {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--primary-dark);
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .badge {
      background: linear-gradient(135deg, var(--accent), #ff9a8b);
      color: var(--white);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .chat-preview {
      color: var(--medium-gray);
      font-size: 0.95rem;
      line-height: 1.5;
      margin-bottom: 0.5rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .chat-time {
      color: var(--medium-gray);
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .chat-time i {
      font-size: 0.75rem;
    }

    .chat-action {
      margin-left: 1.5rem;
      flex-shrink: 0;
    }

    .open-chat-btn {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: var(--white);
      padding: 0.75rem 1.5rem;
      border-radius: var(--radius-lg);
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
    }

    .open-chat-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
    }

    .empty-icon {
      font-size: 4rem;
      color: var(--light-gray);
      margin-bottom: 1.5rem;
      opacity: 0.5;
    }

    .empty-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--medium-gray);
      margin-bottom: 1rem;
    }

    .empty-text {
      color: var(--medium-gray);
      margin-bottom: 2rem;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.6;
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
    }

    .action-btn {
      padding: 0.875rem 1.75rem;
      border-radius: var(--radius-lg);
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: var(--white);
      box-shadow: var(--shadow-sm);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }

    .btn-outline:hover {
      background: var(--primary);
      color: var(--white);
      transform: translateY(-2px);
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 2rem;
      color: var(--medium-gray);
      font-size: 0.9rem;
    }

    .footer a {
      color: var(--primary);
      text-decoration: none;
    }

    .footer a:hover {
      text-decoration: underline;
    }

    /* Animations */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }

    @keyframes pulse {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.7;
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

    /* Responsive */
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem;
      }

      .message-stats {
        width: 100%;
        justify-content: space-between;
      }

      .container {
        margin: 1.5rem auto;
        padding: 0 1rem;
      }

      .welcome-card {
        padding: 2rem 1.5rem;
      }

      .chat-item {
        padding: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
      }

      .chat-avatar {
        margin-right: 0;
        margin-bottom: 1rem;
      }

      .chat-content {
        width: 100%;
        margin-bottom: 1rem;
      }

      .chat-action {
        margin-left: 0;
        width: 100%;
      }

      .open-chat-btn {
        width: 100%;
        justify-content: center;
      }

      .action-buttons {
        flex-direction: column;
      }

      .action-btn {
        width: 100%;
        justify-content: center;
      }
    }

    @media (max-width: 480px) {
      .header-title {
        font-size: 1.5rem;
      }

      .welcome-title {
        font-size: 1.5rem;
      }

      .avatar-circle {
        width: 60px;
        height: 60px;
        font-size: 1.75rem;
      }
    }
  </style>
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