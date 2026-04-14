<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['p_id'])) {
    exit;
}

$p_id = (int)$_SESSION['p_id'];

$recentQuery = $conn->query("
    SELECT a.*, u.username, u.profile_picture as user_pic,
           (SELECT COUNT(*) FROM messages m 
            WHERE m.appointment_id = a.appointment_id 
            AND m.sender_type = 'user' 
            AND m.seen = 0) as unread_count
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.p_id = '$p_id'
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 10
");

if ($recentQuery->num_rows > 0) {
    while ($row = $recentQuery->fetch_assoc()) {
        ?>
        <tr id="appointment-<?= $row['appointment_id']; ?>">
            <td>
                <div class="user-cell">
                    <img src="<?php echo !empty($row['user_pic']) ? htmlspecialchars($row['user_pic']) : 'images/default-user.jpg'; ?>"
                        alt="User" class="user-avatar">
                    <span><?php echo htmlspecialchars($row['username']); ?></span>
                </div>
            </td>
            <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
            <td><?php echo date('g:i A', strtotime($row['appointment_time'])); ?></td>
            <td>
                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                    <?php echo $row['status']; ?>
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    <form action="update_status.php" method="POST" style="display: inline;">
                        <input type="hidden" name="appointment_id" value="<?= $row['appointment_id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="margin-right: 0;">
                            <option value="Pending" <?= strtolower($row['status']) == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= strtolower($row['status']) == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= strtolower($row['status']) == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </form>

                    <?php if (strtolower(trim($row['status'])) === 'approved'): ?>
                        <a href="chat/psychologist_chat.php?appointment_id=<?= $row['appointment_id']; ?>"
                            class="action-btn btn-chat" data-appointment="<?= $row['appointment_id']; ?>">
                            <i class="fas fa-comments"></i> Chat
                            <span class="notification-badge chat-badge" id="badge-<?= $row['appointment_id']; ?>" 
                                  style="display: <?= $row['unread_count'] > 0 ? 'inline-block' : 'none'; ?>">
                                <?= $row['unread_count']; ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
} else {
    ?>
    <tr>
        <td colspan="5">
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>No appointments yet</h3>
                <p>When patients book sessions, they'll appear here.</p>
            </div>
        </td>
    </tr>
    <?php
}
?>