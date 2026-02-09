<?php
session_start();
include '../db_connect.php';
include 'check_appointment.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
if (!isset($_GET['appointment_id'])) {
    die("Appointment missing.");
}

$appointment_id = $_GET['appointment_id'];
$user_id = $_SESSION['user_id'];

if (!checkAppointment($conn, $appointment_id, $user_id, null)) {
    die("Chat available after appointment approval.");
}

// Get psychologist name for header
$psych_name = "";
$sql = "SELECT p.username FROM appointments a JOIN psychologist p ON a.p_id = p.p_id WHERE a.appointment_id = ? AND a.user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $psych_name = $row['username'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | MindCare</title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="s.css">

</head>

<body>

    <!-- Chat Container -->
    <div class="chat-container">

        <!-- Chat Header -->
        <div class="chat-header">
            <div class="header-left">
                <a href="messages_list_user.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>

                <div class="psych-info">
                    <div class="psych-avatar">
                        <?php echo strtoupper(substr($psych_name, 0, 1)); ?>
                    </div>
                    <div class="psych-details">
                        <h2><?php echo htmlspecialchars($psych_name); ?></h2>
                        <div class="psych-status">
                            
                            <span> Licensed Therapist</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Chat Box -->
        <div id="chatBox" class="chat-box">

            <div id="welcomeMessage" class="welcome-message">
                <div class="welcome-icon">
                    <i class="fas fa-comment-medical"></i>
                </div>
                <h3>Start Your Conversation</h3>
                <p class="welcome-text">
                    This is a safe, confidential space to communicate with your therapist.
                    Your conversations are encrypted and protected by professional standards.
                </p>
            </div>

        </div>

        <!-- Chat Input -->
        <div class="chat-input">
            <div style="flex: 1;">
                <textarea
                    id="msg"
                    class="message-input"
                    placeholder="Write your message here..."
                    rows="1"
                    oninput="autoResize(this)"></textarea>

            </div>

            <button class="send-btn" onclick="sendMsg()" id="sendButton">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

    </div>

    <script>
        // Auto-resize textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        // Load chat messages
        function loadChat() {
            fetch("load_messages.php?appointment_id=<?php echo $appointment_id; ?>")
                .then(res => res.json())
                .then(messages => {
                    let box = document.getElementById("chatBox");
                    let atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 50;

                    // Clear except welcome message and typing indicator
                    Array.from(box.children).forEach(child => {
                        if (!child.id || !['welcomeMessage', 'typingIndicator'].includes(child.id)) {
                            child.remove();
                        }
                    });

                    // Hide welcome message if we have messages
                    const welcomeMessage = document.getElementById('welcomeMessage');
                    if (messages.length > 0) {
                        welcomeMessage.style.display = 'none';

                        // Group messages by date
                        let currentDate = null;
                        messages.forEach((m, index) => {
                            // Use created_at timestamp from database
                            const timestamp = m.created_at ? new Date(m.created_at) : new Date();
                            const messageDate = timestamp.toDateString();

                            // Add date separator if date changed
                            if (messageDate !== currentDate) {
                                currentDate = messageDate;
                                const dateDiv = document.createElement('div');
                                dateDiv.className = 'chat-date';
                                dateDiv.innerHTML = `<span class="date-label">${formatDate(timestamp)}</span>`;
                                box.appendChild(dateDiv);
                            }

                            // Create message wrapper
                            let wrapper = document.createElement("div");
                            wrapper.classList.add("message-wrapper");

                            if (m.sender_type === "user") {
                                wrapper.classList.add("user");

                                wrapper.innerHTML = `
                            <div class="msg user-msg">
                                ${escapeHtml(m.message)}
                                <div class="message-time">
                                    <i class="far fa-clock"></i>
                                    ${formatTime(timestamp)}
                                </div>
                            </div>
                            <div class="status ${m.seen==1?"seen":m.delivered==1?"delivered":"sending"}">
                                <i class="fas ${m.seen==1?"fa-eye":m.delivered==1?"fa-check-double":"fa-check"}"></i>
                                ${m.seen==1 ? "Seen" : m.delivered==1 ? "Delivered" : "Sending..."}
                            </div>
                        `;
                            } else {
                                wrapper.classList.add("psych");

                                wrapper.innerHTML = `
                            <div class="msg psych-msg">
                                ${escapeHtml(m.message)}
                                <div class="message-time">
                                    <i class="far fa-clock"></i>
                                    ${formatTime(timestamp)}
                                </div>
                            </div>
                        `;
                            }

                            box.appendChild(wrapper);
                        });
                    } else {
                        welcomeMessage.style.display = 'block';
                    }

                    // Scroll to bottom if user was near bottom
                    if (atBottom) {
                        setTimeout(() => {
                            box.scrollTop = box.scrollHeight;
                        }, 100);
                    }

                    // Mark psychologist messages as seen
                    fetch("mark_seen.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "appointment_id=<?php echo $appointment_id; ?>"
                    }).then(() => {
                        // Update status indicators
                        updateMessageStatus();
                    });
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        // Send message
        function sendMsg() {
            let messageInput = document.getElementById("msg");
            let message = messageInput.value.trim();

            if (message === "") return;

            // Disable send button and show sending state
            const sendButton = document.getElementById('sendButton');
            const originalIcon = sendButton.innerHTML;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendButton.disabled = true;

            // Add temporary message to UI
            const tempId = Date.now();
            addTemporaryMessage(message, tempId);

            fetch("send_message.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "appointment_id=<?php echo $appointment_id; ?>&sender=user&message=" + encodeURIComponent(message)
                })
                .then(response => {
                    if (response.ok) {
                        // Clear input
                        messageInput.value = "";
                        messageInput.style.height = 'auto';

                        // Remove temporary message
                        removeTemporaryMessage(tempId);

                        // Reload messages to get actual message with ID
                        setTimeout(loadChat, 500);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    // Update temporary message status to error
                    updateTemporaryMessageStatus(tempId, 'error');
                })
                .finally(() => {
                    // Re-enable send button
                    sendButton.innerHTML = originalIcon;
                    sendButton.disabled = false;
                });
        }

        // Add temporary message to UI
        function addTemporaryMessage(message, tempId) {
            const box = document.getElementById("chatBox");
            const wrapper = document.createElement("div");
            wrapper.id = `temp-${tempId}`;
            wrapper.className = "message-wrapper user";
            wrapper.innerHTML = `
                <div class="msg user-msg">
                    ${escapeHtml(message)}
                    <div class="message-time">
                        <i class="far fa-clock"></i>
                        Just now
                    </div>
                </div>
                <div class="status sending">
                    <i class="fas fa-spinner fa-spin"></i>
                    Sending...
                </div>
            `;
            box.appendChild(wrapper);
            box.scrollTop = box.scrollHeight;
        }

        // Remove temporary message
        function removeTemporaryMessage(tempId) {
            const element = document.getElementById(`temp-${tempId}`);
            if (element) {
                element.remove();
            }
        }

        // Update temporary message status
        function updateTemporaryMessageStatus(tempId, status) {
            const element = document.getElementById(`temp-${tempId}`);
            if (element) {
                const statusDiv = element.querySelector('.status');
                if (status === 'error') {
                    statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed to send';
                    statusDiv.className = 'status sending';
                    statusDiv.style.color = '#dc3545';
                }
            }
        }


        // Format time
        function formatTime(date) {
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Format date
        function formatDate(date) {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === today.toDateString()) {
                return 'Today';
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            } else {
                return date.toLocaleDateString([], {
                    weekday: 'long',
                    month: 'short',
                    day: 'numeric'
                });
            }
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Send message on Enter key (Shift+Enter for new line)
        document.getElementById('msg').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMsg();
            }
        });

        // Initial load
        loadChat();

        setInterval(loadChat, 400);

        // Focus input on load
        window.addEventListener('load', () => {
            document.getElementById('msg').focus();
        });

        // Add scroll to bottom button when scrolled up
        let scrollTimeout;
        const chatBox = document.getElementById('chatBox');
        chatBox.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);

            // Show scroll to bottom button if not near bottom
            if (chatBox.scrollTop + chatBox.clientHeight < chatBox.scrollHeight - 200) {
                // You could add a "scroll to bottom" button here
            }
        });
    </script>
</body>

</html>