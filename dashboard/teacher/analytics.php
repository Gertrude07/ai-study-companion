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

$pageTitle = 'Analytics';
$teacherObj = new Teacher();
$teacherId = $_SESSION['user_id'];

// Get enrolled students only
$students = $teacherObj->getAllStudents($teacherId);

// Calculate stats from enrolled students
$totalNotes = 0;
$totalQuizzes = 0;
$totalScore = 0;
$quizCount = 0;

foreach ($students as $student) {
    $totalNotes += $student['note_count'];
    $totalQuizzes += $student['quiz_count'];
    if ($student['quiz_count'] > 0) {
        $totalScore += $student['avg_score'];
        $quizCount++;
    }
}

$stats = [
    'total_students' => count($students),
    'total_notes' => $totalNotes,
    'total_quizzes' => $totalQuizzes,
    'avg_score' => $quizCount > 0 ? round($totalScore / $quizCount, 2) : 0
];
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
                <a href="analytics.php" class="nav-item active">
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
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <h1><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
                <p>Overview of your enrolled students' performance and activity</p>
            </header>

            <!-- Overall Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #DBEAFE;">
                        <i class="fas fa-user-graduate" style="color: #3B82F6;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_students'] ?? 0; ?></h3>
                        <p>Enrolled Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #FEF3C7;">
                        <i class="fas fa-book" style="color: #F59E0B;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_notes'] ?? 0; ?></h3>
                        <p>Total Notes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #D1FAE5;">
                        <i class="fas fa-clipboard-check" style="color: #10B981;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_quizzes'] ?? 0; ?></h3>
                        <p>Quizzes Taken</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #E0E7FF;">
                        <i class="fas fa-star" style="color: #6366F1;"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['avg_score'] ?? 0; ?>%</h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-trophy"></i> Top Performers (By Average Score)</h2>

                <?php
                // Sort students by average score
                usort($students, function ($a, $b) {
                    return $b['avg_score'] - $a['avg_score'];
                });
                $topStudents = array_slice($students, 0, 10);
                ?>

                <?php if (empty($topStudents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>No quiz data available yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>Quizzes Taken</th>
                                    <th>Average Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                foreach ($topStudents as $student):
                                    if ($student['quiz_count'] == 0)
                                        continue;
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank <= 3): ?>
                                                <span class="rank-badge rank-<?php echo $rank; ?>">
                                                    <i class="fas fa-medal"></i> #<?php echo $rank; ?>
                                                </span>
                                            <?php else: ?>
                                                #<?php echo $rank; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo $student['quiz_count']; ?></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo $student['avg_score']; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                    $rank++;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Most Active Students -->
            <div class="card" style="margin-top: 2rem;">
                <h2><i class="fas fa-fire"></i> Most Active Students (By Notes Uploaded)</h2>

                <?php
                // Sort students by note count
                usort($students, function ($a, $b) {
                    return $b['note_count'] - $a['note_count'];
                });
                $activeStudents = array_slice($students, 0, 10);
                ?>

                <?php if (empty($activeStudents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>No activity data available yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Notes Uploaded</th>
                                    <th>Materials Generated</th>
                                    <th>Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeStudents as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                        <td><?php echo $student['note_count']; ?></td>
                                        <td><?php echo $student['note_count'] * 3; ?> <small>(est.)</small></td>
                                        <td><?php echo $student['last_activity'] ? timeAgo($student['last_activity']) : 'Never'; ?>
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

        .rank-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .rank-1 {
            background: #FEF3C7;
            color: #F59E0B;
        }

        .rank-2 {
            background: #E5E7EB;
            color: #6B7280;
        }

        .rank-3 {
            background: #FED7AA;
            color: #EA580C;
        }

        [data-theme="light"] .data-table th {
            background: #F9FAFB;
            color: #374151;
        }

        [data-theme="light"] .data-table td {
            color: #1F2937;
        }
    </style>
</body>

</html>