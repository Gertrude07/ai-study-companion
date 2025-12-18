<?php
require_once '../config/database.php';
require_once '../classes/Analytics.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Progress & Analytics';
$userId = $_SESSION['user_id'];

// Get analytics data
$db = new Database();
$analytics = new Analytics($db->getConnection());

try {
    $stats = $analytics->getUserStats($userId);
    $studyStreak = $analytics->getStudyStreak($userId);
    $weakTopics = $analytics->getWeakTopics($userId, 5);
    $recentActivity = $analytics->getRecentActivity($userId, 10);
    $performanceOverTime = $analytics->getPerformanceOverTime($userId, 30);
    
} catch (Exception $e) {
    $error = 'Error loading analytics: ' . $e->getMessage();
    $stats = [];
    $studyStreak = 0;
    $weakTopics = [];
    $recentActivity = [];
    $performanceOverTime = [];
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .progress-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            color: #FFFFFF;
            margin-bottom: 0.75rem;
            letter-spacing: -1px;
        }

        .progress-header p {
            color: #9CA3AF;
            font-size: 1.15rem;
            font-weight: 500;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(8, 145, 178, 0.2);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 30px rgba(8, 145, 178, 0.4);
            border-color: rgba(8, 145, 178, 0.5);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            color: #000000;
            margin: 0 0 0.25rem 0;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .stat-info p {
            color: #374151;
            font-size: 1rem;
            margin: 0;
            font-weight: 600;
        }

        .chart-section {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(8, 145, 178, 0.2);
            backdrop-filter: blur(10px);
        }

        .chart-section h2 {
            font-size: 1.75rem;
            color: #000000;
            margin-bottom: 2rem;
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        .chart-container {
            height: 350px;
            position: relative;
        }
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 2rem;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .trend-up {
            color: #10B981;
        }

        .trend-down {
            color: #EF4444;
        }

        .chart-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .chart-section h2 {
            font-size: 1.25rem;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .two-column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .weak-topics-list {
            list-style: none;
            padding: 0;
        }

        .topic-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }

        .topic-rank {
            width: 32px;
            height: 32px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .topic-info {
            flex: 1;
        }

        .topic-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .topic-score {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #E5E7EB;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .activity-icon.quiz {
            background: #DBEAFE;
            color: #3B82F6;
        }

        .activity-icon.flashcard {
            background: #E0E7FF;
            color: #4F46E5;
        }

        .activity-icon.upload {
            background: #D1FAE5;
            color: #10B981;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6B7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .progress-container {
                padding: 1rem;
            }

            .two-column-grid {
                grid-template-columns: 1fr;
            }

            .chart-container {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> AI Study Companion</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="materials.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>My Materials</span>
                </a>
                <a href="upload.php" class="nav-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload Notes</span>
                </a>
                <a href="progress.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Progress</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="progress-container">
                <div style="margin-bottom: 1.5rem;">
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="progress-header">
                    <h1><i class="fas fa-chart-line"></i> Progress & Analytics</h1>
                    <p>Track your learning journey and identify areas for improvement</p>
                </div>

                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background-color: #E0E7FF; color: #4F46E5;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_notes'] ?? 0; ?></h3>
                                <p>Total Notes</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background-color: #DBEAFE; color: #3B82F6;">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_quizzes'] ?? 0; ?></h3>
                                <p>Quizzes Taken</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background-color: #D1FAE5; color: #10B981;">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo round($stats['average_score'] ?? 0); ?>%</h3>
                                <p>Average Score</p>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background-color: #FEF3C7; color: #F59E0B;">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $studyStreak; ?></h3>
                                <p>Day Study Streak</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="chart-section">
                    <h2>
                        <i class="fas fa-chart-area"></i>
                        Performance Over Time
                    </h2>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- Two Column Section -->
                <div class="two-column-grid">
                    <!-- Weak Topics -->
                    <div class="chart-section">
                        <h2>
                            <i class="fas fa-exclamation-triangle"></i>
                            Topics to Review
                        </h2>
                        <?php if (!empty($weakTopics)): ?>
                            <ul class="weak-topics-list">
                                <?php foreach ($weakTopics as $index => $topic): ?>
                                    <li class="topic-item">
                                        <div class="topic-rank"><?php echo $index + 1; ?></div>
                                        <div class="topic-info">
                                            <div class="topic-name"><?php echo htmlspecialchars($topic['title']); ?></div>
                                            <div class="topic-score">Average: <?php echo round($topic['avg_score']); ?>%</div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>No weak topics identified yet. Keep studying!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Activity -->
                    <div class="chart-section">
                        <h2>
                            <i class="fas fa-history"></i>
                            Recent Activity
                        </h2>
                        <?php if (!empty($recentActivity)): ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                            <?php if ($activity['type'] === 'quiz'): ?>
                                                <i class="fas fa-question-circle"></i>
                                            <?php elseif ($activity['type'] === 'flashcard'): ?>
                                                <i class="fas fa-clone"></i>
                                            <?php else: ?>
                                                <i class="fas fa-upload"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                            <div class="activity-meta">
                                                <?php if ($activity['type'] === 'quiz' && isset($activity['score'])): ?>
                                                    Score: <?php echo $activity['score']; ?>% • 
                                                <?php endif; ?>
                                                <?php echo date('M d, Y g:i A', strtotime($activity['date'])); ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No recent activity. Start studying to see your progress!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Performance chart
        const performanceData = <?php echo json_encode($performanceOverTime); ?>;
        
        if (performanceData && performanceData.length > 0) {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: performanceData.map(d => {
                        const date = new Date(d.date);
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Average Score',
                        data: performanceData.map(d => d.score),
                        borderColor: '#0891B2',
                        backgroundColor: 'rgba(8, 145, 178, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#0891B2',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: '700'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return 'Score: ' + Math.round(context.parsed.y) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                font: {
                                    weight: '600'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: '600'
                                }
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('performanceChart').parentElement.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><h3>No quiz data yet</h3><p>Take some quizzes to see your performance trends!</p></div>';
        }
    </script>
</body>
</html>
