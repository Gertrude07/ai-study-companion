<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    header('Location: teacher/discussions.php');
    exit();
}

$pageTitle = 'Class Discussion';
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
                <a href="messages.php" class="nav-item">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="discussions.php" class="nav-item active">
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
                <h1><i class="fas fa-users"></i> Class Discussion</h1>
                <p>Ask questions and discuss with your classmates</p>
            </header>

            <!-- New Discussion Button -->
            <div style="margin-bottom: 2rem;">
                <button onclick="showNewDiscussionModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Discussion
                </button>
            </div>

            <!-- Discussions List -->
            <div id="discussionsList">
                <p style="text-align: center; padding: 2rem;">Loading discussions...</p>
            </div>
        </main>
    </div>

    <!-- New Discussion Modal -->
    <div id="newDiscussionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeNewDiscussionModal()">&times;</span>
            <h2><i class="fas fa-plus"></i> New Discussion</h2>
            <form id="newDiscussionForm" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label for="discussionTitle">Title</label>
                    <input type="text" id="discussionTitle" required>
                </div>
                <div class="form-group">
                    <label for="discussionContent">Your Question or Topic</label>
                    <textarea id="discussionContent" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Post Discussion
                </button>
            </form>
        </div>
    </div>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .close {
            color: var(--text-secondary);
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .discussion-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .discussion-card:hover {
            border-color: #0891B2;
            box-shadow: 0 4px 16px rgba(8, 145, 178, 0.2);
        }

        .pinned-badge {
            background: #F59E0B;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>

    <script>
        function showNewDiscussionModal() {
            document.getElementById('newDiscussionModal').style.display = 'flex';
        }

        function closeNewDiscussionModal() {
            document.getElementById('newDiscussionModal').style.display = 'none';
            document.getElementById('newDiscussionForm').reset();
        }

        // Load discussions
        async function loadDiscussions() {
            try {
                const response = await fetch('../api/get_discussions.php');
                const result = await response.json();

                const container = document.getElementById('discussionsList');

                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(disc => `
                        <div class="discussion-card" id="disc-${disc.discussion_id}">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <h3 style="margin: 0; flex: 1;">${disc.title}</h3>
                                ${disc.is_pinned == 1 ? '<span class="pinned-badge"><i class="fas fa-thumbtack"></i> Pinned</span>' : ''}
                            </div>
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">${disc.content}</p>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">
                                <span>
                                    <i class="fas fa-user"></i> ${disc.author_name} (${disc.author_role})
                                    <span style="margin-left: 1rem;"><i class="fas fa-clock"></i> ${new Date(disc.created_at).toLocaleString()}</span>
                                </span>
                                <button onclick="toggleReplies(${disc.discussion_id})" class="btn btn-sm btn-outline">
                                    <i class="fas fa-comments"></i> ${disc.reply_count} Replies
                                </button>
                            </div>
                            
                            <!-- Replies Section -->
                            <div id="replies-${disc.discussion_id}" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--border-color);">
                                <div id="replies-list-${disc.discussion_id}" style="margin-bottom: 1rem;">
                                    <p style="text-align: center; color: var(--text-secondary);">Loading replies...</p>
                                </div>
                                
                                <!-- Reply Form -->
                                <form onsubmit="postReply(event, ${disc.discussion_id})" style="margin-top: 1rem;">
                                    <textarea id="reply-input-${disc.discussion_id}" placeholder="Add a comment..." rows="2" style="width: 100%; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; resize: vertical;" required></textarea>
                                    <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                </form>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-comments"></i><p>No discussions yet. Start the first one!</p></div>';
                }
            } catch (error) {
                console.error('Error loading discussions:', error);
            }
        }

        // Toggle replies visibility
        async function toggleReplies(discussionId) {
            const repliesDiv = document.getElementById(`replies-${discussionId}`);

            if (repliesDiv.style.display === 'none') {
                repliesDiv.style.display = 'block';
                await loadReplies(discussionId);
            } else {
                repliesDiv.style.display = 'none';
            }
        }

        // Load replies for a discussion
        async function loadReplies(discussionId) {
            try {
                const response = await fetch(`../api/get_discussion_replies.php?discussion_id=${discussionId}`);
                const result = await response.json();

                const container = document.getElementById(`replies-list-${discussionId}`);

                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(reply => `
                        <div style="background: rgba(8, 145, 178, 0.05); padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem; border-left: 3px solid #0891B2;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <strong>${reply.author_name}</strong>
                                <small style="color: var(--text-secondary);">${new Date(reply.created_at).toLocaleString()}</small>
                            </div>
                            <p style="margin: 0; color: var(--text-primary);">${reply.content}</p>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="text-align: center; color: var(--text-secondary); font-style: italic;">No replies yet. Be the first to comment!</p>';
                }
            } catch (error) {
                console.error('Error loading replies:', error);
            }
        }

        // Post a reply
        async function postReply(event, discussionId) {
            event.preventDefault();

            const input = document.getElementById(`reply-input-${discussionId}`);
            const content = input.value.trim();

            if (!content) return;

            try {
                const formData = new FormData();
                formData.append('discussion_id', discussionId);
                formData.append('content', content);

                const response = await fetch('../api/reply_discussion.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    input.value = '';
                    await loadReplies(discussionId);
                    await loadDiscussions(); // Refresh to update reply count
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error posting reply:', error);
                alert('Failed to post reply');
            }
        }

        // Post new discussion
        document.getElementById('newDiscussionForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const title = document.getElementById('discussionTitle').value.trim();
            const content = document.getElementById('discussionContent').value.trim();

            try {
                const formData = new FormData();
                formData.append('title', title);
                formData.append('content', content);

                const response = await fetch('../api/post_discussion.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    closeNewDiscussionModal();
                    loadDiscussions();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error posting discussion:', error);
                alert('Failed to post discussion');
            }
        });

        // Initial load
        loadDiscussions();
        setInterval(loadDiscussions, 30000); // Refresh every 30 seconds
    </script>
</body>

</html>