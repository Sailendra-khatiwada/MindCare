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
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Chat</title>
    <link rel="stylesheet" href="s.css">
</head>

<body>

    <div class="chat-wrapper">

        <div class="chat-header">
            <a href="messages_list_user.php" class="back-btn">⟵ Back</a>
            <span>Chat With Psychologist</span>
        </div>

        <div id="chatBox" class="chat-box"></div>

        <div class="chat-input">
            <textarea id="msg" placeholder="Write a message..."></textarea>
            <button onclick="sendMsg()">➤</button>
        </div>

    </div>

 <script>
let lastMessageCount = 0;

function loadChat() {
    fetch("load_messages.php?appointment_id=<?php echo $appointment_id; ?>")
        .then(res => res.json())
        .then(messages => {
            let box = document.getElementById("chatBox");

            let atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 5;

            box.innerHTML = "";

            messages.forEach(m => {
                let bubble = document.createElement("div");
                bubble.classList.add("msg");

                if (m.sender_type === "user") {
                    bubble.classList.add("user-msg");

                    if (m.seen == 1) {
                        bubble.innerHTML = `
                            ${m.message}
                            <div class="seen">Seen</div>
                        `;
                    } else if (m.delivered == 1) {
                        bubble.innerHTML = `
                            ${m.message}
                            <div class="delivered">Delivered</div>
                        `;
                    } else {
                        bubble.innerHTML = `
                            ${m.message}
                            <div class="delivered">Sending...</div>
                        `;
                    }

                } else {
                    bubble.classList.add("psych-msg");
                    bubble.innerHTML = m.message;
                }

                box.appendChild(bubble);
            });

            if (atBottom) box.scrollTop = box.scrollHeight;

            // Mark psychologist messages as seen
            fetch("mark_seen.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "appointment_id=<?php echo $appointment_id; ?>"
            });
        });
}

setInterval(loadChat, 400);

function sendMsg() {
    let message = document.getElementById("msg").value;
    if (message.trim() === "") return;

    fetch("send_message.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "appointment_id=<?php echo $appointment_id; ?>&sender=user&message=" + encodeURIComponent(message)
    });

    document.getElementById("msg").value = "";
}

</script>


</body>

</html>

