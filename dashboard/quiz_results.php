<?php
// Disable error display to users (errors still logged to error.log)
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../classes/Quiz.php';
require_once '../classes/Note.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in
requireLogin();

$pageTitle = 'Quiz Results';
$userId = $_SESSION['user_id'];

// Get attempt_id from URL
if (!isset($_GET['attempt_id'])) {
    header('Location: materials.php');
    exit;
}

$attemptId = (int) $_GET['attempt_id'];

// Get quiz results
$quiz = new Quiz();
$note = new Note();

try {
    $results = $quiz->getAttemptResults($attemptId, $userId);

    if (!$results) {
        $_SESSION['error'] = 'Quiz results not found.';
        header('Location: materials.php');
        exit;
    }

    // Get note details
    $noteDetails = $note->getById($results['note_id'], $userId);
    $noteTitle = $noteDetails['title'] ?? 'Study Material';

} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading results: ' . $e->getMessage();
    header('Location: materials.php');
    exit;
}

// Calculate percentage
$percentage = $results['total_questions'] > 0
    ? round(($results['correct_answers'] / $results['total_questions']) * 100)
    : 0;

// Determine grade
function getGrade($percentage)
{
    if ($percentage >= 90)
        return ['grade' => 'A', 'color' => '#10B981', 'message' => 'Excellent!'];
    if ($percentage >= 80)
        return ['grade' => 'B', 'color' => '#3B82F6', 'message' => 'Great job!'];
    if ($percentage >= 70)
        return ['grade' => 'C', 'color' => '#F59E0B', 'message' => 'Good work!'];
    if ($percentage >= 60)
        return ['grade' => 'D', 'color' => '#EF4444', 'message' => 'Needs improvement'];
    return ['grade' => 'F', 'color' => '#DC2626', 'message' => 'Keep practicing'];
}

$gradeInfo = getGrade($percentage);

// Format duration (handle missing duration field)
$duration = $results['duration'] ?? 0;
$minutes = floor($duration / 60);
$seconds = $duration % 60;
$durationText = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
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
    <style>
        .results-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .results-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .results-header h1 {
            font-size: 1.75rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .note-title {
            font-size: 1rem;
            color: #6B7280;
        }

        .score-card {
            background: white;
            border-radius: 16px;
            padding: 3rem 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 2rem;
        }

        .grade-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            background: #F9FAFB;
        }

        .grade-circle::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            background: conic-gradient(var(--grade-color) calc(var(--percentage) * 1%),
                    #E5E7EB calc(var(--percentage) * 1%));
            z-index: -1;
        }

        .grade-letter {
            font-size: 4rem;
            font-weight: 700;
            color: var(--grade-color);
            line-height: 1;
        }

        .grade-percentage {
            font-size: 1.5rem;
            color: #6B7280;
            font-weight: 600;
        }

        .grade-message {
            font-size: 1.25rem;
            color: #111827;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .score-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: #F9FAFB;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 1rem;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .answers-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .answers-section h2 {
            font-size: 1.5rem;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .answer-item {
            padding: 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .answer-item.correct {
            border-color: #10B981;
            background-color: #ECFDF5;
        }

        .answer-item.incorrect {
            border-color: #EF4444;
            background-color: #FEF2F2;
        }

        .answer-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .answer-status {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .answer-item.correct .answer-status {
            background: #10B981;
            color: white;
        }

        .answer-item.incorrect .answer-status {
            background: #EF4444;
            color: white;
        }

        .question-number {
            font-weight: 600;
            color: #6B7280;
        }

        .question-text {
            font-size: 1.125rem;
            color: #111827;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .answer-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .answer-row {
            display: flex;
            gap: 0.5rem;
            font-size: 0.9375rem;
        }

        .answer-label {
            font-weight: 600;
            color: #6B7280;
            min-width: 120px;
        }

        .answer-value {
            color: #111827;
        }

        .answer-item.correct .answer-value.your-answer {
            color: #10B981;
            font-weight: 600;
        }

        .answer-item.incorrect .answer-value.your-answer {
            color: #EF4444;
            font-weight: 600;
        }

        .correct-answer-value {
            color: #10B981;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #4F46E5;
            border: 2px solid #E5E7EB;
        }

        .btn-secondary:hover {
            background: #F5F3FF;
            border-color: #4F46E5;
        }

        @media (max-width: 768px) {
            .results-container {
                padding: 1rem;
            }

            .score-card {
                padding: 2rem 1rem;
            }

            .grade-circle {
                width: 150px;
                height: 150px;
            }

            .grade-letter {
                font-size: 3rem;
            }

            .answers-section {
                padding: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
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
                <a href="materials.php" class="nav-item active">
                    <i class="fas fa-book"></i>
                    <span>My Materials</span>
                </a>
                <a href="upload.php" class="nav-item">
                    <i class="fas fa-upload"></i>
                    <span>Upload Notes</span>
                </a>
                <a href="progress.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Progress</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="results-container">
                <div style="margin-bottom: 1.5rem;">
                    <a href="materials.php?note_id=<?php echo $results['note_id']; ?>" class="btn btn-secondary"
                        style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Materials
                    </a>
                </div>
                <div class="results-header">
                    <h1><i class="fas fa-trophy"></i> Quiz Results</h1>
                    <div class="note-title"><?php echo htmlspecialchars($noteTitle); ?></div>
                </div>

                <div class="score-card"
                    style="--percentage: <?php echo $percentage; ?>; --grade-color: <?php echo $gradeInfo['color']; ?>;">
                    <div class="grade-circle">
                        <div class="grade-letter"><?php echo $gradeInfo['grade']; ?></div>
                        <div class="grade-percentage"><?php echo $percentage; ?>%</div>
                    </div>
                    <div class="grade-message"><?php echo $gradeInfo['message']; ?></div>

                    <div class="score-stats">
                        <div class="stat-box">
                            <div class="stat-icon" style="color: #10B981;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-value"><?php echo $results['correct_answers']; ?></div>
                            <div class="stat-label">Correct Answers</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-icon" style="color: #EF4444;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-value">
                                <?php echo $results['total_questions'] - $results['correct_answers']; ?>
                            </div>
                            <div class="stat-label">Incorrect Answers</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-icon" style="color: #3B82F6;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo $durationText; ?></div>
                            <div class="stat-label">Time Taken</div>
                        </div>
                    </div>
                </div>

                <div class="answers-section">
                    <h2>
                        <i class="fas fa-list-check"></i>
                        Detailed Review
                    </h2>

                    <?php foreach ($results['questions'] as $index => $question): ?>
                        <div class="answer-item <?php echo $question['is_correct'] ? 'correct' : 'incorrect'; ?>">
                            <div class="answer-header">
                                <div class="answer-status">
                                    <?php if ($question['is_correct']): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="question-number">Question <?php echo $index + 1; ?></span>
                            </div>

                            <div class="question-text">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>

                            <div class="answer-details">
                                <div class="answer-row">
                                    <span class="answer-label">Your Answer:</span>
                                    <span class="answer-value your-answer">
                                        <?php echo htmlspecialchars($question['user_answer'] ?: 'Not answered'); ?>
                                    </span>
                                </div>
                                <?php if (!$question['is_correct']): ?>
                                    <div class="answer-row">
                                        <span class="answer-label">Correct Answer:</span>
                                        <span class="answer-value correct-answer-value">
                                            <?php echo htmlspecialchars($question['correct_answer']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="action-buttons">
                    <button onclick="generateMoreQuiz()" class="action-btn" id="generateMoreBtn"
                        style="background: #F59E0B; color: white;">
                        <i class="fas fa-plus"></i>
                        Generate More Quiz
                    </button>
                    <a href="quiz.php?note_id=<?php echo $results['note_id']; ?>" class="action-btn btn-secondary">
                        <i class="fas fa-redo"></i>
                        Retake Quiz
                    </a>
                    <a href="flashcards.php?note_id=<?php echo $results['note_id']; ?>"
                        class="action-btn btn-secondary">
                        <i class="fas fa-clone"></i>
                        Study Flashcards
                    </a>
                    <a href="materials.php" class="action-btn btn-primary">
                        <i class="fas fa-book"></i>
                        Back to Materials
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function generateMoreQuiz() {
            const btn = document.getElementById('generateMoreBtn');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            try {
                const formData = new FormData();
                formData.append('note_id', <?php echo $results['note_id']; ?>);
                formData.append('type', 'quiz');
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/generate_more.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message + '\nYou can take the new quiz from the materials page!');
                    location.href = 'materials.php?note_id=<?php echo $results['note_id']; ?>';
                } else {
                    alert('❌ ' + (result.message || 'Failed to generate quiz'));
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }
    </script>
</body>

</html>