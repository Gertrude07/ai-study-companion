<?php
require_once '../config/database.php';
require_once '../classes/StudyMaterial.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Take Quiz';
$userId = $_SESSION['user_id'];

// Get note_id from URL
if (!isset($_GET['note_id'])) {
    header('Location: materials.php');
    exit;
}

$noteId = (int)$_GET['note_id'];

// Get quiz questions for this note
$studyMaterial = new StudyMaterial();

try {
    $materials = $studyMaterial->getAllByNote($noteId);
    
    // Find the quiz material
    $quizMaterial = null;
    foreach ($materials as $material) {
        if ($material['material_type'] === 'quiz_set') {
            $quizMaterial = $material;
            break;
        }
    }
    
    if (!$quizMaterial) {
        $_SESSION['error'] = 'No quiz available for this note.';
        header('Location: materials.php');
        exit;
    }
    
    $questions = $studyMaterial->getQuizQuestions($quizMaterial['material_id']);
    
    if (empty($questions)) {
        $_SESSION['error'] = 'No questions found.';
        header('Location: materials.php');
        exit;
    }
    
    $noteTitle = 'Quiz';
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading quiz: ' . $e->getMessage();
    header('Location: materials.php');
    exit;
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
    <style>
        .quiz-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .quiz-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .quiz-header h1 {
            font-size: 1.75rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .quiz-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E5E7EB;
        }

        .quiz-meta {
            display: flex;
            gap: 2rem;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .quiz-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quiz-meta i {
            color: #4F46E5;
        }

        .quiz-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #4F46E5;
        }

        .progress-bar {
            width: 150px;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .question-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: none;
        }

        .question-card.active {
            display: block;
        }

        .question-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .question-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }

        .question-type {
            background: #E0E7FF;
            color: #4F46E5;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .question-text {
            font-size: 1.25rem;
            color: #111827;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .answer-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .option-item {
            position: relative;
        }

        .option-item input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-item input[type="radio"]:checked + .option-label {
            border-color: #4F46E5;
            background-color: #F5F3FF;
        }

        .option-label:hover {
            border-color: #C7D2FE;
            background-color: #FAF9FF;
        }

        .option-letter {
            width: 32px;
            height: 32px;
            background: #F3F4F6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6B7280;
            flex-shrink: 0;
        }

        .option-item input[type="radio"]:checked + .option-label .option-letter {
            background: #4F46E5;
            color: white;
        }

        .option-text {
            flex: 1;
            color: #374151;
        }

        .text-answer {
            width: 100%;
            padding: 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .text-answer:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }

        .nav-btn {
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
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: white;
            color: #4F46E5;
            border: 2px solid #E5E7EB;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #F5F3FF;
            border-color: #4F46E5;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .submit-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            display: none;
        }

        .submit-section.active {
            display: block;
        }

        .submit-section h2 {
            font-size: 1.5rem;
            color: #111827;
            margin-bottom: 1rem;
        }

        .submit-section p {
            color: #6B7280;
            margin-bottom: 2rem;
        }

        .answer-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .summary-item {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .summary-item.answered {
            background: #D1FAE5;
            color: #059669;
        }

        .summary-item.unanswered {
            background: #FEE2E2;
            color: #DC2626;
        }

        @media (max-width: 768px) {
            .quiz-container {
                padding: 1rem;
            }

            .quiz-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .question-card {
                padding: 1.5rem;
            }

            .question-text {
                font-size: 1.125rem;
            }

            .navigation-buttons {
                flex-direction: column;
            }

            .btn-primary {
                margin-left: 0;
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
            <div class="quiz-container">
                <div style="margin-bottom: 1.5rem;">
                    <a href="materials.php?note_id=<?php echo $noteId; ?>" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Materials
                    </a>
                </div>
                <div class="quiz-header">
                    <h1><i class="fas fa-question-circle"></i> Quiz: <?php echo htmlspecialchars($noteTitle); ?></h1>
                    <div class="quiz-info">
                        <div class="quiz-meta">
                            <span>
                                <i class="fas fa-list-ol"></i>
                                <?php echo count($questions); ?> Questions
                            </span>
                            <span>
                                <i class="fas fa-clock"></i>
                                <span id="timer">00:00</span>
                            </span>
                        </div>
                        <div class="quiz-progress">
                            <span id="progress-text">0/<?php echo count($questions); ?></span>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="quiz-form">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card <?php echo $index === 0 ? 'active' : ''; ?>" data-question="<?php echo $index; ?>">
                            <div class="question-header">
                                <div class="question-number"><?php echo $index + 1; ?></div>
                                <div class="question-type"><?php echo $question['question_type']; ?></div>
                            </div>
                            
                            <div class="question-text">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </div>

                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                <?php $options = is_array($question['options']) ? $question['options'] : json_decode($question['options'], true); ?>
                                <div class="answer-options">
                                    <?php foreach ($options as $optIndex => $option): ?>
                                        <div class="option-item">
                                            <input 
                                                type="radio" 
                                                name="question_<?php echo $question['question_id']; ?>" 
                                                value="<?php echo htmlspecialchars($option); ?>"
                                                id="q<?php echo $question['question_id']; ?>_opt<?php echo $optIndex; ?>"
                                            >
                                            <label for="q<?php echo $question['question_id']; ?>_opt<?php echo $optIndex; ?>" class="option-label">
                                                <div class="option-letter"><?php echo chr(65 + $optIndex); ?></div>
                                                <div class="option-text"><?php echo htmlspecialchars($option); ?></div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <textarea 
                                    name="question_<?php echo $question['question_id']; ?>" 
                                    class="text-answer" 
                                    rows="4" 
                                    placeholder="Type your answer here..."
                                ></textarea>
                            <?php endif; ?>

                            <div class="navigation-buttons">
                                <button type="button" class="nav-btn btn-secondary prev-question" <?php echo $index === 0 ? 'style="visibility: hidden;"' : ''; ?>>
                                    <i class="fas fa-arrow-left"></i>
                                    Previous
                                </button>
                                <?php if ($index < count($questions) - 1): ?>
                                    <button type="button" class="nav-btn btn-primary next-question">
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="nav-btn btn-primary" id="review-btn">
                                        Review Answers
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="submit-section">
                        <h2><i class="fas fa-clipboard-check"></i> Review Your Answers</h2>
                        <p>Before submitting, make sure you've answered all questions.</p>
                        
                        <div class="answer-summary" id="answer-summary"></div>

                        <div class="navigation-buttons" style="justify-content: center;">
                            <button type="button" class="nav-btn btn-secondary" id="back-to-quiz">
                                <i class="fas fa-arrow-left"></i>
                                Back to Quiz
                            </button>
                            <button type="submit" class="nav-btn btn-primary">
                                Submit Quiz
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Ask for Clarification Section (for current question) -->
                <div id="clarificationBox" style="margin-top: 2rem; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 2px solid #FEF3C7;">
                    <h3 style="margin: 0 0 0.75rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-lightbulb" style="color: #F59E0B;"></i>
                        Stuck on This Question?
                    </h3>
                    <p style="color: #6B7280; margin-bottom: 1rem; font-size: 0.875rem;">Ask AI for help understanding the concept or clarifying the question.</p>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="quizQuestion" placeholder="E.g., Can you explain what this question is asking?" 
                               style="flex: 1; padding: 0.75rem; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 0.95rem;">
                        <button onclick="askQuizClarification()" id="quizAskBtn" 
                                style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s;">
                            <i class="fas fa-paper-plane"></i> Get Help
                        </button>
                    </div>
                    <div id="quizResponse" style="margin-top: 1rem; display: none; padding: 1.25rem; background: #FFFBEB; border-radius: 8px; border-left: 4px solid #F59E0B;">
                        <h4 style="margin: 0 0 0.75rem 0; color: #D97706; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem;">
                            <i class="fas fa-robot"></i>
                            AI Explanation:
                        </h4>
                        <div id="quizContent" style="color: #374151; line-height: 1.8; font-size: 0.95rem;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Quiz data
        const questions = <?php echo json_encode($questions); ?>;
        const noteId = <?php echo $noteId; ?>;
        let currentQuestion = 0;
        let startTime = Date.now();

        // Timer
        setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const seconds = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = `${minutes}:${seconds}`;
        }, 1000);

        // Navigation
        document.querySelectorAll('.next-question').forEach(btn => {
            btn.addEventListener('click', () => {
                if (currentQuestion < questions.length - 1) {
                    showQuestion(currentQuestion + 1);
                }
            });
        });

        document.querySelectorAll('.prev-question').forEach(btn => {
            btn.addEventListener('click', () => {
                if (currentQuestion > 0) {
                    showQuestion(currentQuestion - 1);
                }
            });
        });

        function showQuestion(index) {
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            
            document.querySelector(`[data-question="${index}"]`).classList.add('active');
            currentQuestion = index;
            updateProgress();
        }

        // Progress tracking
        function updateProgress() {
            const answered = getAnsweredCount();
            const total = questions.length;
            const percentage = (answered / total) * 100;
            
            document.getElementById('progress-text').textContent = `${answered}/${total}`;
            document.getElementById('progress-fill').style.width = `${percentage}%`;
        }

        function getAnsweredCount() {
            let count = 0;
            questions.forEach(q => {
                const answer = getAnswer(q.question_id);
                if (answer) count++;
            });
            return count;
        }

        function getAnswer(questionId) {
            const input = document.querySelector(`[name="question_${questionId}"]`);
            if (!input) return null;
            
            if (input.type === 'radio') {
                const checked = document.querySelector(`[name="question_${questionId}"]:checked`);
                return checked ? checked.value : null;
            } else {
                return input.value.trim() || null;
            }
        }

        // Track answers
        document.querySelectorAll('input[type="radio"], textarea').forEach(input => {
            input.addEventListener('change', updateProgress);
            if (input.tagName === 'TEXTAREA') {
                input.addEventListener('input', updateProgress);
            }
        });

        // Review button
        document.getElementById('review-btn').addEventListener('click', () => {
            showReviewSection();
        });

        function showReviewSection() {
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            
            document.querySelector('.submit-section').classList.add('active');
            
            // Generate summary
            const summaryEl = document.getElementById('answer-summary');
            summaryEl.innerHTML = '';
            
            questions.forEach((q, index) => {
                const div = document.createElement('div');
                div.className = 'summary-item';
                div.textContent = index + 1;
                
                const answer = getAnswer(q.question_id);
                if (answer) {
                    div.classList.add('answered');
                    div.title = 'Answered';
                } else {
                    div.classList.add('unanswered');
                    div.title = 'Not answered';
                }
                
                div.addEventListener('click', () => {
                    document.querySelector('.submit-section').classList.remove('active');
                    showQuestion(index);
                });
                
                summaryEl.appendChild(div);
            });
        }

        document.getElementById('back-to-quiz').addEventListener('click', () => {
            document.querySelector('.submit-section').classList.remove('active');
            showQuestion(currentQuestion);
        });

        // Form submission
        document.getElementById('quiz-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const unanswered = questions.length - getAnsweredCount();
            if (unanswered > 0) {
                if (!confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) {
                    return;
                }
            }
            
            // Collect answers
            const answers = {};
            questions.forEach(q => {
                answers[q.question_id] = getAnswer(q.question_id) || '';
            });
            
            const duration = Math.floor((Date.now() - startTime) / 1000);
            
            // Submit to server
            try {
                const response = await fetch('../api/submit_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        note_id: noteId,
                        answers: answers,
                        duration: duration
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = `quiz_results.php?attempt_id=${result.attempt_id}`;
                } else {
                    alert('Error submitting quiz: ' + result.message);
                }
            } catch (error) {
                alert('Error submitting quiz. Please try again.');
                console.error(error);
            }
        });

        // Initialize
        updateProgress();

        // Clarification function
        async function askQuizClarification() {
            const questionInput = document.getElementById('quizQuestion');
            const askBtn = document.getElementById('quizAskBtn');
            const responseDiv = document.getElementById('quizResponse');
            const contentDiv = document.getElementById('quizContent');
            
            const question = questionInput.value.trim();
            
            if (!question) {
                alert('Please enter a question');
                return;
            }
            
            const currentQ = questions[currentQuestion];
            let contextText = `Quiz Question: ${currentQ.question_text}\n`;
            contextText += `Question Type: ${currentQ.question_type}\n`;
            if (currentQ.question_type === 'multiple_choice' && currentQ.options) {
                contextText += `Options: ${currentQ.options.join(', ')}`;
            }
            
            // Disable button and show loading
            askBtn.disabled = true;
            askBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            responseDiv.style.display = 'block';
            contentDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is preparing a detailed explanation...';
            
            try {
                const response = await fetch('../api/get_clarification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        note_id: noteId,
                        question: question,
                        context: contextText
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Format the explanation
                    let formatted = result.explanation;
                    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
                    formatted = formatted.replace(/### (.*?)(\n|$)/g, '<h4 style="color: #111827; margin: 1rem 0 0.5rem 0;">$1</h4>');
                    formatted = formatted.replace(/\n/g, '<br>');
                    
                    contentDiv.innerHTML = formatted;
                } else {
                    contentDiv.innerHTML = '<span style="color: #DC2626;">Error: ' + (result.message || 'Failed to get clarification') + '</span>';
                }
            } catch (error) {
                contentDiv.innerHTML = '<span style="color: #DC2626;">Error: ' + error.message + '</span>';
            } finally {
                askBtn.disabled = false;
                askBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Get Help';
            }
        }
        
        // Allow Enter key to submit
        document.getElementById('quizQuestion').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                askQuizClarification();
            }
        });
    </script>
</body>
</html>
