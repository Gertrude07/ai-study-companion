<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    header('Location: teacher/messages.php');
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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/light-theme.css?v=<?php echo time(); ?>">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <script src="../assets/js/logout-confirm.js"></script>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Student Portal</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="upload.php" class="nav-item">
                    <i class="fas fa-upload"></i> Upload Notes
                </a>
                <a href="materials.php" class="nav-item">
                    <i class="fas fa-book"></i> My Materials
                </a>
                <a href="messages.php" class="nav-item active">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="discussions.php" class="nav-item">
                    <i class="fas fa-users"></i> Class Discussion
                </a>
                <a href="progress.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i> Progress
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-comments"></i> Messages</h1>
                <p>Chat with your teacher</p>
            </header>

            <div class="card" style="max-width: 900px; margin: 0 auto;">
                <!-- Teacher Selector -->
                <div id="teacherSelector" style="margin-bottom: 1rem; display: none;">
                    <label for="teacherSelect" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">
                        <i class="fas fa-chalkboard-teacher"></i> Select Teacher to Message:
                    </label>
                    <select id="teacherSelect"
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; font-size: 1rem; background: var(--bg-primary); color: var(--text-primary);">
                        <option value="">-- Choose a teacher --</option>
                    </select>
                </div>

                <!-- Chat Header -->
                <div id="chatHeader"
                    style="border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1rem;">
                    <h2 id="chatTitle"><i class="fas fa-user-circle"></i> Loading...</h2>
                </div>

                <!-- Messages Container -->
                <div id="messagesContainer"
                    style="min-height: 400px; max-height: 500px; overflow-y: auto; padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; margin-bottom: 1rem;">
                    <p style="text-align: center; color: var(--text-secondary);">Loading messages...</p>
                </div>

                <!-- Message Form -->
                <form id="messageForm">
                    <input type="hidden" id="receiverId" value="">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="messageInput" placeholder="Type your message..."
                            style="flex: 1; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px;"
                            required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <style>
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            word-wrap: break-word;
            clear: both;
        }

        .message-sent {
            background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%);
            color: white !important;
            float: right;
            border-bottom-right-radius: 4px;
        }

        .message-sent * {
            color: white !important;
        }

        .message-received {
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            color: #1F2937 !important;
            float: left;
            border-bottom-left-radius: 4px;
        }

        .message-received * {
            color: #1F2937 !important;
        }

        [data-theme="dark"] .message-received {
            background: #2D3748;
            border-color: #4A5568;
            color: #E2E8F0 !important;
        }

        [data-theme="dark"] .message-received * {
            color: #E2E8F0 !important;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
    </style>

    <script>
        let currentReceiverId = null;
        let messageRefreshInterval = null;
        let allTeachers = [];

        // Load teachers and setup selector
        async function loadTeacherChat() {
            try {
                console.log('Loading teachers...');
                const response = await fetch('../api/get_my_teacher.php');
                const result = await response.json();
                console.log('Teachers API response:', result);

                if (result.success && result.data && result.data.length > 0) {
                    allTeachers = result.data;
                    const teacherSelect = document.getElementById('teacherSelect');
                    const teacherSelector = document.getElementById('teacherSelector');
                    
                    // Populate dropdown
                    teacherSelect.innerHTML = '<option value="">-- Choose a teacher --</option>';
                    allTeachers.forEach(teacher => {
                        teacherSelect.innerHTML += `<option value="${teacher.user_id}">${teacher.full_name}</option>`;
                    });

                    // If multiple teachers, show selector
                    if (allTeachers.length > 1) {
                        teacherSelector.style.display = 'block';
                        document.getElementById('chatTitle').innerHTML = `<i class="fas fa-users"></i> Select a teacher to start chatting`;
                        document.getElementById('messagesContainer').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Please select a teacher from the dropdown above to view messages.</p>';
                    } else {
                        // Single teacher - auto-select
                        teacherSelector.style.display = 'none';
                        selectTeacher(allTeachers[0].user_id, allTeachers[0].full_name);
                    }
                } else {
                    document.getElementById('messagesContainer').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">You need to join a class first! Use the "Join Class" button in the sidebar.</p>';
                    document.getElementById('chatTitle').innerHTML = '<i class="fas fa-user-circle"></i> No Teacher';
                }
            } catch (error) {
                console.error('Error loading teachers:', error);
                document.getElementById('messagesContainer').innerHTML = '<p style="text-align: center; color: #EF4444;">Failed to load teacher information. Please refresh the page.</p>';
            }
        }

        // Select a teacher for messaging
        function selectTeacher(teacherId, teacherName) {
            currentReceiverId = teacherId;
            document.getElementById('receiverId').value = teacherId;
            document.getElementById('chatTitle').innerHTML = `<i class="fas fa-user-circle"></i> ${teacherName} (Teacher)`;
            
            loadMessages(teacherId);

            // Auto-refresh messages
            if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            messageRefreshInterval = setInterval(() => loadMessages(teacherId), 5000);
        }

        // Handle teacher selection change
        document.getElementById('teacherSelect').addEventListener('change', function() {
            const selectedId = this.value;
            if (selectedId) {
                const selectedTeacher = allTeachers.find(t => t.user_id == selectedId);
                if (selectedTeacher) {
                    selectTeacher(selectedTeacher.user_id, selectedTeacher.full_name);
                }
            } else {
                // Cleared selection
                currentReceiverId = null;
                document.getElementById('receiverId').value = '';
                document.getElementById('chatTitle').innerHTML = '<i class="fas fa-users"></i> Select a teacher to start chatting';
                document.getElementById('messagesContainer').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Please select a teacher from the dropdown above to view messages.</p>';
                if (messageRefreshInterval) clearInterval(messageRefreshInterval);
            }
        });

        // Load messages
        async function loadMessages(userId) {
            try {
                console.log('Loading messages for user:', userId);
                const response = await fetch(`../api/get_messages.php?user_id=${userId}`);
                const result = await response.json();
                console.log('Messages API response:', result);

                const container = document.getElementById('messagesContainer');

                if (result.success) {
                    if (result.data && result.data.length > 0) {
                        container.innerHTML = result.data.map(msg => {
                            const isSent = msg.sender_id == <?php echo $_SESSION['user_id']; ?>;
                            return `
                                <div class="message-bubble ${isSent ? 'message-sent' : 'message-received'}">
                                    <div>${escapeHtml(msg.message_text)}</div>
                                    <div class="message-time">${new Date(msg.sent_at).toLocaleString()}</div>
                                </div>
                            `;
                        }).join('') + '<div style="clear: both;"></div>';
                        container.scrollTop = container.scrollHeight;
                    } else {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No messages yet. Start the conversation!</p>';
                    }
                } else {
                    console.error('Failed to load messages:', result.message);
                    container.innerHTML = '<p style="text-align: center; color: #EF4444;">Error loading messages: ' + result.message + '</p>';
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById('messagesContainer').innerHTML = '<p style="text-align: center; color: #EF4444;">Failed to load messages. Please refresh the page.</p>';
            }
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

                const response = await fetch('../api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Send message response:', result);

                if (result.success) {
                    messageInput.value = '';
                    await loadMessages(receiverId);
                } else {
                    alert('Failed to send message: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            }
        });

        // Initial load
        loadTeacherChat();
    </script>
</body>

</html>