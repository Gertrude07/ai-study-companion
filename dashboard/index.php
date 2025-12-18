<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/Analytics.php';

requireLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Get user stats
$noteObj = new Note();
$quizObj = new Quiz();
$analyticsObj = new Analytics();

$totalNotes = $noteObj->getCountByUser($userId);
$totalQuizzes = $quizObj->getTotalQuizCount($userId);
$averageScore = $quizObj->getAverageScore($userId);
$recentNotes = $noteObj->getRecentByUser($userId, 5);

// Get enrolled teachers
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$conn = $database->getConnection();
$query = "SELECT u.user_id, u.full_name, u.email, ts.enrolled_date 
          FROM teacher_students ts 
          JOIN users u ON ts.teacher_id = u.user_id 
          WHERE ts.student_id = :student_id 
          ORDER BY ts.enrolled_date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':student_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$enrolledTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
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
        // Load theme immediately to prevent flash
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <script src="../assets/js/logout-confirm.js"></script>
</head>

<body>
    <!-- Navigation Sidebar -->
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Study Companion</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
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
                <a href="discussions.php" class="nav-item">
                    <i class="fas fa-users"></i> Class Discussion
                </a>
                <a href="#" onclick="showJoinClassModal(); return false;" class="nav-item">
                    <i class="fas fa-user-plus"></i> Join Class
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
                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>! 👋</h1>
                <p>Here's your learning overview</p>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #DBEAFE;">
                        <i class="fas fa-book" style="color: #3B82F6;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalNotes; ?></h3>
                        <p>Total Notes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #D1FAE5;">
                        <i class="fas fa-clipboard-check" style="color: #10B981;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalQuizzes; ?></h3>
                        <p>Quizzes Taken</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #FEF3C7;">
                        <i class="fas fa-star" style="color: #F59E0B;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $averageScore; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #E0E7FF;">
                        <i class="fas fa-fire" style="color: #6366F1;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $analyticsObj->getStudyStreak($userId); ?> days</h3>
                        <p>Study Streak</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="upload.php" class="action-btn">
                        <i class="fas fa-upload"></i>
                        <span>Upload New Notes</span>
                    </a>
                    <a href="materials.php" class="action-btn">
                        <i class="fas fa-book-open"></i>
                        <span>Browse Materials</span>
                    </a>
                    <a href="progress.php" class="action-btn">
                        <i class="fas fa-chart-line"></i>
                        <span>View Analytics</span>
                    </a>
                </div>
            </div>

            <!-- Recent Materials -->
            <div class="recent-section">
                <h2>Recent Notes</h2>
                <?php if (empty($recentNotes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No notes uploaded yet</p>
                        <a href="upload.php" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Your First Note
                        </a>
                    </div>
                <?php else: ?>
                    <div class="materials-list">
                        <?php foreach ($recentNotes as $note): ?>
                            <div class="material-item">
                                <div class="material-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="material-info">
                                    <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                    <p class="material-meta">
                                        <i class="fas fa-clock"></i>
                                        Uploaded <?php echo timeAgo($note['upload_date']); ?>
                                    </p>
                                </div>
                                <div class="material-actions">
                                    <a href="materials.php?note_id=<?php echo $note['note_id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        View Materials
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="materials.php" class="btn btn-outline">View All Materials</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Classes Section -->
            <div class="recent-section">
                <h2>My Classes</h2>
                <?php if (empty($enrolledTeachers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <p>Not enrolled in any class yet</p>
                        <a href="#" onclick="showJoinClassModal(); return false;" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Join a Class
                        </a>
                    </div>
                <?php else: ?>
                    <div class="materials-list">
                        <?php foreach ($enrolledTeachers as $teacher): ?>
                            <div class="material-item">
                                <div class="material-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="material-info">
                                    <h3><?php echo htmlspecialchars($teacher['full_name']); ?></h3>
                                    <p class="material-meta">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($teacher['email']); ?>
                                    </p>
                                    <p class="material-meta">
                                        <i class="fas fa-calendar"></i>
                                        Joined <?php echo timeAgo($teacher['enrolled_date']); ?>
                                    </p>
                                </div>
                                <div class="material-actions">
                                    <button
                                        onclick="confirmLeaveClass(<?php echo $teacher['user_id']; ?>, '<?php echo htmlspecialchars($teacher['full_name'], ENT_QUOTES); ?>')"
                                        class="btn btn-sm" style="background: #EF4444; color: white;">
                                        <i class="fas fa-sign-out-alt"></i> Leave
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        /* Modal Styles */
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
            margin: auto;
            padding: 2.5rem;
            border: 1px solid rgba(8, 145, 178, 0.3);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content h2 {
            margin-top: 0;
            color: var(--text-primary);
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .modal-content p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .close {
            color: var(--text-secondary);
            float: right;
            font-size: 2rem;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: var(--error-color);
        }

        .success-message {
            background: #D1FAE5;
            color: #10B981;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #10B981;
        }

        .error-message {
            background: #FEE2E2;
            color: #EF4444;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #EF4444;
        }
    </style>

    <!-- Join Class Modal -->
    <div id="joinClassModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeJoinClassModal()">&times;</span>
            <h2><i class="fas fa-user-plus"></i> Join a Class</h2>
            <p>Enter your teacher's class code to join their class</p>

            <form id="joinClassForm" style="margin-top: 1.5rem;">
                <div class="form-group">
                    <label for="classCode"><i class="fas fa-key"></i> Class Code</label>
                    <input type="text" id="classCode" name="class_code" placeholder="e.g., ABC-X3Y"
                        style="text-transform: uppercase; letter-spacing: 0.1em; font-size: 1.2rem; text-align: center;"
                        required>
                    <small class="form-hint">Ask your teacher for their class code</small>
                </div>

                <div id="joinMessage" style="display: none; margin-bottom: 1rem;"></div>

                <button type="submit" class="btn btn-primary btn-block" id="joinBtn">
                    <i class="fas fa-check"></i> Join Class
                </button>
            </form>
        </div>
    </div>

    <script>
        function showJoinClassModal() {
            document.getElementById('joinClassModal').style.display = 'flex';
            document.getElementById('classCode').focus();
        }

        function closeJoinClassModal() {
            document.getElementById('joinClassModal').style.display = 'none';
            document.getElementById('joinClassForm').reset();
            document.getElementById('joinMessage').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('joinClassModal');
            if (event.target === modal) {
                closeJoinClassModal();
            }
        }

        // Handle form submission
        document.getElementById('joinClassForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const classCode = document.getElementById('classCode').value.trim().toUpperCase();
            const joinBtn = document.getElementById('joinBtn');
            const joinMessage = document.getElementById('joinMessage');

            // Disable button
            joinBtn.disabled = true;
            joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';

            try {
                const formData = new FormData();
                formData.append('class_code', classCode);

                const response = await fetch('../api/join_class.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    joinMessage.innerHTML = `<div class="success-message">${result.message}</div>`;
                    joinMessage.style.display = 'block';

                    setTimeout(() => {
                        closeJoinClassModal();
                        location.reload(); // Reload to show updated info
                    }, 2000);
                } else {
                    joinMessage.innerHTML = `<div class="error-message">${result.message}</div>`;
                    joinMessage.style.display = 'block';
                    joinBtn.disabled = false;
                    joinBtn.innerHTML = '<i class="fas fa-check"></i> Join Class';
                }
            } catch (error) {
                joinMessage.innerHTML = '<div class="error-message">An error occurred. Please try again.</div>';
                joinMessage.style.display = 'block';
                joinBtn.disabled = false;
                joinBtn.innerHTML = '<i class="fas fa-check"></i> Join Class';
            }
        });

        // Leave class function
        async function confirmLeaveClass(teacherId, teacherName) {
            if (!confirm(`Are you sure you want to leave ${teacherName}'s class? You will lose access to class discussions and messages.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('teacher_id', teacherId);

                const response = await fetch('../api/leave_class.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload(); // Reload to show updated list
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
    </script>
</body>

</html>