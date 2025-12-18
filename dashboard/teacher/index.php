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

$pageTitle = 'Teacher Dashboard';
$teacherObj = new Teacher();
$teacherId = $_SESSION['user_id'];

// Get class code
$classCode = $teacherObj->getClassCode($teacherId);

// Get enrolled students only
$students = $teacherObj->getAllStudents($teacherId);

// Get system statistics (for this teacher's students only)
$enrollmentCount = $teacherObj->getEnrollmentCount($teacherId);
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
                <a href="index.php" class="nav-item active">
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
                <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
                <p>Monitor your enrolled students' progress and performance</p>
            </header>

            <!-- Class Code Card -->
            <div class="card"
                style="background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%); color: white; margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="color: white; margin-bottom: 0.5rem;"><i class="fas fa-key"></i> Your Class Code</h2>
                        <p style="color: rgba(255,255,255,0.9); margin-bottom: 1rem;">Share this code with students to
                            let them join your class</p>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div id="classCodeDisplay"
                                style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: 12px; font-size: 2rem; font-weight: bold; letter-spacing: 0.1em;">
                                <?php echo $classCode; ?>
                            </div>
                            <button onclick="copyClassCode()" class="btn btn-secondary"
                                style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.5);">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold;"><?php echo $enrollmentCount; ?></div>
                        <div style="color: rgba(255,255,255,0.9);">Enrolled Students</div>
                    </div>
                </div>
            </div>

            <!-- Students List -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2><i class="fas fa-users"></i> Enrolled Students (<?php echo count($students); ?>)</h2>
                    <input type="text" id="searchInput" placeholder="Search students..."
                        style="padding: 0.5rem 1rem; border: 2px solid #E5E7EB; border-radius: 8px; width: 300px;">
                </div>

                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <p>No students enrolled yet</p>
                        <small>Share your class code above with students to get started!</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Notes</th>
                                    <th>Quizzes</th>
                                    <th>Avg Score</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo $student['note_count']; ?></td>
                                        <td><?php echo $student['quiz_count']; ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $student['avg_score'] >= 70 ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo $student['avg_score']; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo $student['last_activity'] ? timeAgo($student['last_activity']) : 'Never'; ?>
                                        </td>
                                        <td>
                                            <a href="student_view.php?id=<?php echo $student['user_id']; ?>"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        .data-table th {
            background: #F9FAFB;
            font-weight: 600;
            color: #374151;
        }

        .data-table tbody tr:hover {
            background: #F9FAFB;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .badge-success {
            background: #D1FAE5;
            color: #10B981;
        }

        .badge-warning {
            background: #FEF3C7;
            color: #F59E0B;
        }

        [data-theme="light"] .data-table th {
            background: #F9FAFB;
            color: #374151;
        }

        [data-theme="light"] .data-table td {
            color: #1F2937;
        }
    </style>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#studentTableBody tr');

            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();

                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Copy class code to clipboard
        function copyClassCode() {
            const classCode = '<?php echo $classCode; ?>';
            const btn = document.querySelector('button[onclick="copyClassCode()"]');

            navigator.clipboard.writeText(classCode).then(() => {
                // Show success feedback
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = 'rgba(16, 185, 129, 0.3)';

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = 'rgba(255,255,255,0.2)';
                }, 2000);
            }).catch(err => {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>

</html>