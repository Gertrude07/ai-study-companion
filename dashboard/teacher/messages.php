<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

if ($_SESSION['role'] !== 'teacher') {
    header('Location: ../messages.php');
    exit();
}

$pageTitle = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AI Study Companion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/light-theme.css?v=<?php echo time(); ?>">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <script src="../../assets/js/logout-confirm.js"></script>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Teacher Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-users"></i> All Students
                </a>
                <a href="messages.php" class="nav-item active">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="discussions.php" class="nav-item">
                    <i class="fas fa-users"></i> Class Discussion
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <a href="../settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-comments"></i> Student Messages</h1>
                <p>Chat with your enrolled students</p>
            </header>

            <div style="display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; height: calc(100vh - 250px);">
                <!-- Conversations List -->
                <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                    <h2 style="margin-bottom: 1rem;"><i class="fas fa-inbox"></i> Conversations</h2>
                    <div id="conversationsList" style="flex: 1; overflow-y: auto;">
                        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">Loading...</p>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                    <div id="chatHeader"
                        style="border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
                        <p style="color: var(--text-secondary);">Select a student to start chatting</p>
                    </div>

                    <div id="messagesContainer"
                        style="flex: 1; overflow-y: auto; padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px;">
                        <!-- Messages will be loaded here -->
                    </div>

                    <form id="messageForm" style="margin-top: 1rem; display: none;">
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="hidden" id="receiverId" value="">
                            <input type="text" id="messageInput" placeholder="Type your message..."
                                style="flex: 1; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px;"
                                required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }

        .conversation-item:hover {
            background: rgba(8, 145, 178, 0.1);
        }

        .conversation-item.active {
            background: rgba(8, 145, 178, 0.2);
            border-left: 4px solid #0891B2;
        }

        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            word-wrap: break-word;
        }

        .message-sent {
            background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message-received {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .unread-badge {
            background: #EF4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>

    <script>
        let currentReceiverId = null;
        let messageRefreshInterval = null;

        // Load conversations
        async function loadConversations() {
            try {
                const response = await fetch('../../api/get_conversations.php');
                const result = await response.json();

                const container = document.getElementById('conversationsList');

                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(conv => `
                        <div class="conversation-item" onclick="openConversation(${conv.other_user_id}, '${conv.other_user_name}')">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div style="flex: 1;">
                                    <strong>${conv.other_user_name}</strong>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);"> (${conv.other_user_role})</span>
                                    <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${conv.last_message || 'No messages yet'}
                                    </p>
                                </div>
                                ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No conversations yet. Students will appear here when they message you.</p>';
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        // Open conversation
        async function openConversation(userId, userName) {
            currentReceiverId = userId;
            document.getElementById('receiverId').value = userId;
            document.getElementById('chatHeader').innerHTML = `<h2><i class="fas fa-user-circle"></i> ${userName}</h2>`;
            document.getElementById('messageForm').style.display = 'flex';

            // Highlight active conversation
            document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            await loadMessages(userId);

            // Auto-refresh messages
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(() => loadMessages(userId), 5000);
        }

        // Load messages
        async function loadMessages(userId) {
            try {
                const response = await fetch(`../../api/get_messages.php?user_id=${userId}`);
                const result = await response.json();

                const container = document.getElementById('messagesContainer');

                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(msg => {
                        const isSent = msg.sender_id == <?php echo $_SESSION['user_id']; ?>;
                        return `
                            <div style="display: flex; justify-content: ${isSent ? 'flex-end' : 'flex-start'};">
                                <div class="message-bubble ${isSent ? 'message-sent' : 'message-received'}">
                                    <div>${msg.message_text}</div>
                                    <div class="message-time">${new Date(msg.sent_at).toLocaleString()}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    container.scrollTop = container.scrollHeight;
                } else {
                    container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No messages yet. Start the conversation!</p>';
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        // Send message
        document.getElementById('messageForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const receiverId = document.getElementById('receiverId').value;

            if (!message || !receiverId) return;

            try {
                const formData = new FormData();
                formData.append('receiver_id', receiverId);
                formData.append('message', message);

                const response = await fetch('../../api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageInput.value = '';
                    await loadMessages(receiverId);
                    await loadConversations();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            }
        });

        // Initial load
        loadConversations();
    </script>
</body>

</html>