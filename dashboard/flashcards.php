<?php
require_once '../config/database.php';
require_once '../classes/StudyMaterial.php';
require_once '../includes/functions.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Study Flashcards';
$userId = $_SESSION['user_id'];

// Get note_id from URL
if (!isset($_GET['note_id'])) {
    header('Location: materials.php');
    exit;
}

$noteId = (int) $_GET['note_id'];

// Get flashcards for this note
$studyMaterial = new StudyMaterial();

try {
    $materials = $studyMaterial->getAllByNote($noteId);

    // Find all flashcard sets
    $flashcardSets = [];
    foreach ($materials as $material) {
        if ($material['material_type'] === 'flashcard_set') {
            $flashcardSets[] = $material;
        }
    }

    if (empty($flashcardSets)) {
        $_SESSION['error'] = 'No flashcards available for this note.';
        header('Location: materials.php');
        exit;
    }

    // Get selected set (default to first set)
    $selectedSet = isset($_GET['set']) ? (int) $_GET['set'] : 0;
    if ($selectedSet < 0 || $selectedSet >= count($flashcardSets)) {
        $selectedSet = 0;
    }

    $flashcardMaterial = $flashcardSets[$selectedSet];
    $flashcards = $studyMaterial->getFlashcards($flashcardMaterial['material_id']);

    if (empty($flashcards)) {
        $_SESSION['error'] = 'No flashcards found in this set.';
        header('Location: materials.php');
        exit;
    }

    $noteTitle = 'Flashcards - Set ' . ($selectedSet + 1) . ' of ' . count($flashcardSets);

} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading flashcards: ' . $e->getMessage();
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
        .flashcard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .flashcard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .flashcard-header h1 {
            font-size: 1.75rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .flashcard-header .note-title {
            font-size: 1rem;
            color: #6B7280;
            margin-bottom: 1rem;
        }

        .progress-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .progress-info span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-info i {
            color: #4F46E5;
        }

        .flashcard-wrapper {
            perspective: 1000px;
            margin-bottom: 2rem;
        }

        .flashcard {
            position: relative;
            width: 100%;
            min-height: 400px;
            cursor: pointer;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flashcard.flipped {
            transform: rotateY(180deg);
        }

        .flashcard-face {
            position: absolute;
            width: 100%;
            min-height: 400px;
            backface-visibility: hidden;
            border-radius: 16px;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .flashcard-front {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .flashcard-back {
            background: white;
            color: #111827;
            transform: rotateY(180deg);
            border: 2px solid #E5E7EB;
        }

        .flashcard-label {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .flashcard-content {
            font-size: 1.5rem;
            line-height: 1.6;
            max-width: 600px;
        }

        .flashcard-back .flashcard-content {
            color: #374151;
        }

        .flip-hint {
            position: absolute;
            bottom: 1.5rem;
            font-size: 0.875rem;
            opacity: 0.7;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.7;
            }

            50% {
                opacity: 0.4;
            }
        }

        .navigation-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
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
            text-decoration: none;
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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

        .keyboard-shortcuts {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .keyboard-shortcuts h3 {
            font-size: 1rem;
            color: #111827;
            margin-bottom: 1rem;
        }

        .shortcuts-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .shortcut-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .key {
            background: #F3F4F6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 600;
            color: #111827;
        }

        @media (max-width: 768px) {
            .flashcard-container {
                padding: 1rem;
            }

            .flashcard-face {
                min-height: 350px;
                padding: 2rem 1.5rem;
            }

            .flashcard-content {
                font-size: 1.25rem;
            }

            .navigation-buttons {
                flex-direction: column;
            }

            .nav-btn {
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
            <div class="flashcard-container">
                <div style="margin-bottom: 1.5rem;">
                    <a href="materials.php?note_id=<?php echo $noteId; ?>" class="btn btn-secondary"
                        style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Materials
                    </a>
                </div>
                <div class="flashcard-header">
                    <h1><i class="fas fa-clone"></i> Study Flashcards</h1>
                    <div class="note-title"><?php echo htmlspecialchars($noteTitle); ?></div>

                    <?php if (count($flashcardSets) > 1): ?>
                        <div style="margin: 1rem 0; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php for ($i = 0; $i < count($flashcardSets); $i++): ?>
                                <a href="?note_id=<?php echo $noteId; ?>&set=<?php echo $i; ?>"
                                    class="btn <?php echo $i === $selectedSet ? 'btn-primary' : 'btn-secondary'; ?>"
                                    style="padding: 0.5rem 1rem;">
                                    Set <?php echo $i + 1; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <div class="progress-info">
                        <span>
                            <i class="fas fa-layer-group"></i>
                            <strong id="current-card">1</strong> / <?php echo count($flashcards); ?>
                        </span>
                        <span>
                            <i class="fas fa-percentage"></i>
                            Progress: <strong id="progress-percent">0%</strong>
                        </span>
                    </div>
                </div>

                <div class="flashcard-wrapper">
                    <div class="flashcard" id="flashcard">
                        <div class="flashcard-face flashcard-front">
                            <div class="flashcard-label">Question</div>
                            <div class="flashcard-content" id="question-text">
                                <?php echo htmlspecialchars($flashcards[0]['question']); ?>
                            </div>
                            <div class="flip-hint">
                                <i class="fas fa-sync-alt"></i> Click or press Space to flip
                            </div>
                        </div>
                        <div class="flashcard-face flashcard-back">
                            <div class="flashcard-label">Answer</div>
                            <div class="flashcard-content" id="answer-text">
                                <?php echo htmlspecialchars($flashcards[0]['answer']); ?>
                            </div>
                            <div class="flip-hint">
                                <i class="fas fa-sync-alt"></i> Click or press Space to flip
                            </div>
                        </div>
                    </div>
                </div>

                <div class="navigation-buttons">
                    <button class="nav-btn btn-secondary" id="prev-btn" disabled>
                        <i class="fas fa-arrow-left"></i>
                        Previous
                    </button>
                    <button class="nav-btn btn-secondary" id="flip-btn">
                        <i class="fas fa-sync-alt"></i>
                        Flip Card
                    </button>
                    <button class="nav-btn btn-primary" id="next-btn">
                        Next
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                <div class="keyboard-shortcuts">
                    <h3><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h3>
                    <div class="shortcuts-list">
                        <div class="shortcut-item">
                            <span class="key">Space</span>
                            <span>Flip card</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="key">←</span>
                            <span>Previous card</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="key">→</span>
                            <span>Next card</span>
                        </div>
                        <div class="shortcut-item">
                            <span class="key">Esc</span>
                            <span>Back to materials</span>
                        </div>
                    </div>
                </div>

                <!-- Ask for Clarification Section -->
                <div
                    style="max-width: 800px; margin: 2rem auto; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3
                        style="margin: 0 0 0.75rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem; font-size: 1.125rem;">
                        <i class="fas fa-question-circle" style="color: #10B981;"></i>
                        Need Help With This Flashcard?
                    </h3>
                    <p style="color: #6B7280; margin-bottom: 1rem; font-size: 0.875rem;">Get a detailed explanation of
                        the current question or answer.</p>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="flashcardQuestion"
                            placeholder="E.g., Can you explain this in more detail?"
                            style="flex: 1; padding: 0.75rem; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 0.95rem;">
                        <button onclick="askFlashcardClarification()" id="flashcardAskBtn"
                            style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: all 0.2s;">
                            <i class="fas fa-paper-plane"></i> Ask AI
                        </button>
                    </div>
                    <div id="flashcardResponse"
                        style="margin-top: 1rem; display: none; padding: 1.25rem; background: #F0FDF4; border-radius: 8px; border-left: 4px solid #10B981;">
                        <h4
                            style="margin: 0 0 0.75rem 0; color: #059669; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem;">
                            <i class="fas fa-robot"></i>
                            AI Explanation:
                        </h4>
                        <div id="flashcardContent" style="color: #374151; line-height: 1.8; font-size: 0.95rem;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Flashcard data from PHP
        const flashcards = <?php echo json_encode($flashcards); ?>;
        const noteId = <?php echo $noteId; ?>;

        let currentIndex = 0;
        let isFlipped = false;

        // DOM elements
        const flashcard = document.getElementById('flashcard');
        const questionText = document.getElementById('question-text');
        const answerText = document.getElementById('answer-text');
        const currentCardEl = document.getElementById('current-card');
        const progressPercent = document.getElementById('progress-percent');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const flipBtn = document.getElementById('flip-btn');

        // Update card display
        function updateCard() {
            const card = flashcards[currentIndex];
            questionText.textContent = card.question;
            answerText.textContent = card.answer;
            currentCardEl.textContent = currentIndex + 1;

            // Calculate progress
            const progress = Math.round((currentIndex / flashcards.length) * 100);
            progressPercent.textContent = progress + '%';

            // Update button states
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex === flashcards.length - 1;

            // Reset flip state
            if (isFlipped) {
                flipCard();
            }

            // Update button text for last card
            if (currentIndex === flashcards.length - 1) {
                nextBtn.innerHTML = '<i class="fas fa-check"></i> Finish';
            } else {
                nextBtn.innerHTML = 'Next <i class="fas fa-arrow-right"></i>';
            }
        }

        // Flip card
        function flipCard() {
            flashcard.classList.toggle('flipped');
            isFlipped = !isFlipped;
        }

        // Navigation functions
        function nextCard() {
            if (currentIndex < flashcards.length - 1) {
                currentIndex++;
                updateCard();
            } else {
                // Finished all cards - show completion modal
                showCompletionModal();
            }
        }

        function showCompletionModal() {
            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 9999;';
            modal.innerHTML = `
                <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
                    <h2 style="margin: 0 0 1rem 0;">Set Complete!</h2>
                    <p style="margin-bottom: 2rem; color: #666;">You've finished all ${flashcards.length} flashcards in this set.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <button onclick="generateMoreFlashcards()" class="btn" style="background: #10B981; color: white;">
                            <i class="fas fa-plus"></i> Generate More Flashcards
                        </button>
                        <button onclick="location.href='materials.php?note_id=<?php echo $noteId; ?>'" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Materials
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        async function generateMoreFlashcards() {
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            try {
                const formData = new FormData();
                formData.append('note_id', <?php echo $noteId; ?>);
                formData.append('type', 'flashcards');
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/generate_more.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message + '\\nReloading to show new flashcards...');
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'Failed to generate flashcards'));
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        function prevCard() {
            if (currentIndex > 0) {
                currentIndex--;
                updateCard();
            }
        }

        // Event listeners
        flashcard.addEventListener('click', flipCard);
        flipBtn.addEventListener('click', flipCard);
        nextBtn.addEventListener('click', nextCard);
        prevBtn.addEventListener('click', prevCard);

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Don't trigger shortcuts if user is typing in input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            switch (e.key) {
                case ' ':
                    e.preventDefault();
                    flipCard();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    prevCard();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    nextCard();
                    break;
                case 'Escape':
                    window.location.href = 'materials.php?note_id=<?php echo $noteId; ?>';
                    break;
            }
        });

        // Initialize
        updateCard();

        // Clarification function
        async function askFlashcardClarification() {
            const questionInput = document.getElementById('flashcardQuestion');
            const askBtn = document.getElementById('flashcardAskBtn');
            const responseDiv = document.getElementById('flashcardResponse');
            const contentDiv = document.getElementById('flashcardContent');

            const question = questionInput.value.trim();

            if (!question) {
                alert('Please enter a question');
                return;
            }

            const currentCard = flashcards[currentIndex];

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
                        context: `Flashcard - Question: ${currentCard.question}\nAnswer: ${currentCard.answer}`
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Format the explanation - properly handle markdown
                    let formatted = result.explanation;
                    // Remove ### headers and convert to styled divs
                    formatted = formatted.replace(/###\s+(.*?)(\n|$)/g, '<h4 style="color: #111827; margin: 1.25rem 0 0.75rem 0; font-size: 1.05rem;">$1</h4>');
                    // Convert **bold** to <strong>
                    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    // Convert *italic* to <em>
                    formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
                    // Convert line breaks
                    formatted = formatted.replace(/\n\n/g, '</p><p style="margin: 0.75rem 0;">');
                    formatted = formatted.replace(/\n/g, '<br>');
                    // Wrap in paragraph
                    formatted = '<p style="margin: 0;">' + formatted + '</p>';

                    contentDiv.innerHTML = formatted;
                } else {
                    contentDiv.innerHTML = '<span style="color: #DC2626;">Error: ' + (result.message || 'Failed to get clarification') + '</span>';
                }
            } catch (error) {
                contentDiv.innerHTML = '<span style="color: #DC2626;">Error: ' + error.message + '</span>';
            } finally {
                askBtn.disabled = false;
                askBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Ask AI';
            }
        }

        // Allow Enter key to submit
        document.getElementById('flashcardQuestion').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                askFlashcardClarification();
            }
        });
    </script>
</body>

</html>