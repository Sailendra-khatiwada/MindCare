<?php
session_start();
include '../db_connect.php';
include 'check_appointment.php';

if (!isset($_SESSION['p_id'])) {
    header("Location: ../login.php");
    exit;
}
if (!isset($_GET['appointment_id'])) {
    die("Appointment missing.");
}
$appointment_id = $_GET['appointment_id'];
$p_id = $_SESSION['p_id'];

if (!checkAppointment($conn, $appointment_id, null, $p_id)) {
    die("Chat available after appointment approval.");
}

$user_name = "";
$sql = "SELECT u.username, u.profile_picture FROM appointments a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE a.appointment_id = ? AND a.p_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $appointment_id, $p_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['username'];
        $user_pic = $row['profile_picture'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="s.css">

</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="header-left">
                <a href="../psychologist_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>

                <div class="psych-info">
                    <div class="psych-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="psych-details">
                        <h2><?php echo htmlspecialchars($user_name); ?></h2>
                        <div class="psych-status">
                            <span>Patient</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div id="chatBox" class="chat-box">
            <div id="welcomeMessage" class="welcome-message">
                <div class="welcome-icon">
                    <i class="fas fa-comment-medical"></i>
                </div>
                <h3>Start Your Conversation</h3>
                <p class="welcome-text">
                    This is a safe, confidential space to communicate with your patient.
                    Your conversations are encrypted and protected by professional standards.
                </p>
            </div>

        </div>

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
        let lastMessageTimestamp = new Date().getTime();
        let previousMessageCount = 0;

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        function loadChat() {
            fetch("load_messages.php?appointment_id=<?php echo $appointment_id; ?>")
                .then(res => res.json())
                .then(messages => {
                    let box = document.getElementById("chatBox");
                    let atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 50;
                    Array.from(box.children).forEach(child => {
                        if (!child.id || child.id !== 'welcomeMessage') {
                            child.remove();
                        }
                    });

                    const welcomeMessage = document.getElementById('welcomeMessage');
                    if (messages.length > 0) {
                        welcomeMessage.style.display = 'none';
                        let currentDate = null;
                        messages.forEach((m) => {
                            const timestamp = m.created_at ? new Date(m.created_at) : new Date();
                            const messageDate = timestamp.toDateString();
                            if (messageDate !== currentDate) {
                                currentDate = messageDate;
                                const dateDiv = document.createElement('div');
                                dateDiv.className = 'chat-date';
                                dateDiv.innerHTML = `<span class="date-label">${formatDate(timestamp)}</span>`;
                                box.appendChild(dateDiv);
                            }
                            let wrapper = document.createElement("div");
                            wrapper.classList.add("message-wrapper");

                            if (m.sender_type === "psychologist") {
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

                    if (atBottom) {
                        setTimeout(() => {
                            box.scrollTop = box.scrollHeight;
                        }, 100);
                    }

                    fetch("mark_seen_psych.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "appointment_id=<?php echo $appointment_id; ?>"
                    });
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        function sendMsg() {
            let messageInput = document.getElementById("msg");
            let message = messageInput.value.trim();

            if (message === "") return;
            const sendButton = document.getElementById('sendButton');
            const originalIcon = sendButton.innerHTML;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendButton.disabled = true;
            const tempId = Date.now();
            addTemporaryMessage(message, tempId);
            fetch("send_message.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "appointment_id=<?php echo $appointment_id; ?>&sender=psychologist&message=" + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "OK") {
                        const msg_id = data.msg_id;
                        messageInput.value = "";
                        messageInput.style.height = 'auto';
                        removeTemporaryMessage(tempId);
                        setTimeout(loadChat, 500);
                        
                        setTimeout(() => {
                            fetch("mark_delivered.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: "msg_id=" + msg_id
                            }).then(() => loadChat());
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    updateTemporaryMessageStatus(tempId, 'error');
                })
                .finally(() => {
                    sendButton.innerHTML = originalIcon;
                    sendButton.disabled = false;
                });
        }

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

        function removeTemporaryMessage(tempId) {
            const element = document.getElementById(`temp-${tempId}`);
            if (element) {
                element.remove();
            }
        }

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

        function formatTime(date) {
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

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

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.getElementById('msg').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMsg();
            }
        });

        loadChat();
        setInterval(loadChat, 400);
        window.addEventListener('load', () => {
            document.getElementById('msg').focus();
        });
    </script>
</body>

</html>