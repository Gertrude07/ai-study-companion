<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Teacher.php';

requireLogin();

// Check if user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId === 0) {
    header('Location: index.php');
    exit();
}

$teacherObj = new Teacher();
$student = $teacherObj->getStudentDetails($studentId);

if (!$student) {
    header('Location: index.php');
    exit();
}

$pageTitle = 'Student Details - ' . htmlspecialchars($student['full_name']);
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
                <a href="messages.php" class="nav-item">
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
                <div style="margin-bottom: 1rem;">
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>
                <h1><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['full_name']); ?></h1>
                <p><?php echo htmlspecialchars($student['email']); ?></p>
            </header>

            <!-- Student Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #DBEAFE;">
                        <i class="fas fa-book" style="color: #3B82F6;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $student['stats']['total_notes'] ?? 0; ?></h3>
                        <p>Total Notes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #FEF3C7;">
                        <i class="fas fa-file-alt" style="color: #F59E0B;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $student['stats']['total_materials'] ?? 0; ?></h3>
                        <p>Study Materials</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #D1FAE5;">
                        <i class="fas fa-clipboard-check" style="color: #10B981;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $student['stats']['total_quizzes'] ?? 0; ?></h3>
                        <p>Quizzes Taken</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #E0E7FF;">
                        <i class="fas fa-star" style="color: #6366F1;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $student['stats']['avg_score'] ?? 0; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Uploaded Notes -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-book"></i> Uploaded Notes (<?php echo count($student['notes']); ?>)</h2>

                <?php if (empty($student['notes'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No notes uploaded yet</p>
                    </div>
                <?php else: ?>
                    <div class="materials-list">
                        <?php foreach ($student['notes'] as $note): ?>
                            <div class="material-item">
                                <div class="material-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="material-info">
                                    <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                    <p class="material-meta">
                                        <i class="fas fa-file"></i> <?php echo htmlspecialchars($note['original_filename']); ?>
                                        <span style="margin-left: 1rem;">
                                            <i class="fas fa-clock"></i> Uploaded <?php echo timeAgo($note['upload_date']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quiz Attempts -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-clipboard-check"></i> Quiz Attempts
                    (<?php echo count($student['quiz_attempts']); ?>)</h2>

                <?php if (empty($student['quiz_attempts'])): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No quizzes taken yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Score</th>
                                    <th>Questions</th>
                                    <th>Percentage</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student['quiz_attempts'] as $attempt): ?>
                                    <?php
                                    $percentage = round(($attempt['score'] / $attempt['total_questions']) * 100);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($attempt['note_title']); ?></strong></td>
                                        <td><?php echo $attempt['score']; ?> / <?php echo $attempt['total_questions']; ?></td>
                                        <td><?php echo $attempt['total_questions']; ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $percentage >= 70 ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo $percentage; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo timeAgo($attempt['completed_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Info -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-info-circle"></i> Student Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Joined:</strong>
                        <span><?php echo date('F j, Y', strtotime($student['joined_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Last Activity:</strong>
                        <span>
                            <?php
                            echo $student['stats']['last_activity']
                                ? timeAgo($student['stats']['last_activity'])
                                : 'No activity yet';
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Chat Section -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-comments"></i> Chat with Student</h2>
                <!-- Interface fixed via GitHub Sync -->
                <div id="messagesContainer"
                    style="height: 400px; overflow-y: auto; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1rem;">
                    <div style="text-align: center; color: #64748b; padding-top: 2rem;">Loading messages...</div>
                </div>
                <form id="messageForm" style="display: flex; gap: 0.5rem;">
                    <input type="text" id="messageInput" placeholder="Type a message..."
                        style="flex: 1; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem;" required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Styles from messages.php -->
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
        const studentId = <?php echo $studentId; ?>;  // Load messages
        async function loadMessages() {
            try {
                const response = await fetch(`../../api/get_messages.php?user_id=${studentId}`);
                const result = await response.json();

      const container = document.getElementById('messagesContainer');

                if (result.success && result.data) {
                    if (result.data.length === 0) {
                        container.innerHTML = '<div style="text-align: center; color: #64748b; padding-top: 2rem;">No messages yet. Start the conversation!</div>';
                        return;
                    }

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
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Send message
        document.getElementById('messageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (!message) return;

            try {
                const formData = new FormData();
                formData.append('receiver_id', studentId);
                formData.append('message', message);

                const response = await fetch('../../api/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    input.value = '';
                    loadMessages();
                } else {
                    alert(result.message || 'Failed to send');
                }
            } catch (error) {
                console.error('Error sending:', error);
                alert('Failed to send message');
            }
        });

        // Auto refresh
        loadMessages();
        setInterval(loadMessages, 5000);
    </script>
</body>

</html>