<?php
require_once '../config/database.php';
require_once '../classes/StudyMaterial.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'View Summary';
$userId = $_SESSION['user_id'];

// Get note_id from URL
if (!isset($_GET['note_id'])) {
    header('Location: materials.php');
    exit;
}

$noteId = (int) $_GET['note_id'];

// Get summary for this note
$studyMaterial = new StudyMaterial();

try {
    $materials = $studyMaterial->getAllByNote($noteId);

    // Find the summary material
    $summaryMaterial = null;
    foreach ($materials as $material) {
        if ($material['material_type'] === 'summary') {
            $summaryMaterial = $material;
            break;
        }
    }

    if (!$summaryMaterial) {
        $_SESSION['error'] = 'No summary available for this note.';
        header('Location: materials.php');
        exit;
    }

    $noteTitle = 'Summary';
    $summary = $summaryMaterial['content'];
    $createdAt = $summaryMaterial['created_at'];

} catch (Exception $e) {
    $_SESSION['error'] = 'Error loading summary: ' . $e->getMessage();
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
        .summary-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .summary-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .summary-header h1 {
            font-size: 1.75rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .note-title {
            font-size: 1.25rem;
            color: #4F46E5;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .summary-meta {
            font-size: 0.875rem;
            color: #6B7280;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .summary-content {
            font-size: 1.125rem;
            line-height: 1.8;
            color: #374151;
        }

        .summary-content h1,
        .summary-content h2,
        .summary-content h3 {
            color: #111827;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .summary-content h1 {
            font-size: 1.875rem;
        }

        .summary-content h2 {
            font-size: 1.5rem;
        }

        .summary-content h3 {
            font-size: 1.25rem;
        }

        .summary-content p {
            margin-bottom: 1rem;
        }

        .summary-content ul,
        .summary-content ol {
            margin: 1rem 0;
            padding-left: 1.5rem;
        }

        .summary-content li {
            margin-bottom: 0.5rem;
        }

        .summary-content strong {
            color: #111827;
            font-weight: 600;
        }

        .summary-content em {
            font-style: italic;
            color: #4F46E5;
        }

        .summary-content blockquote {
            border-left: 4px solid #4F46E5;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #6B7280;
        }

        .summary-content code {
            background: #F3F4F6;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
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

        .btn-print {
            background: white;
            color: #6B7280;
            border: 2px solid #E5E7EB;
        }

        .btn-print:hover {
            background: #F9FAFB;
            border-color: #9CA3AF;
        }

        @media print {

            .sidebar,
            .action-buttons {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .summary-card {
                box-shadow: none;
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .summary-container {
                padding: 1rem;
            }

            .summary-card {
                padding: 2rem 1.5rem;
            }

            .summary-content {
                font-size: 1rem;
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
            <div class="summary-container">
                <div style="margin-bottom: 1.5rem;">
                    <a href="materials.php?note_id=<?php echo $noteId; ?>" class="btn btn-secondary"
                        style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Materials
                    </a>
                </div>
                <div class="summary-header">
                    <h1><i class="fas fa-file-alt"></i> Summary</h1>
                    <div class="note-title"><?php echo htmlspecialchars($noteTitle); ?></div>
                    <div class="summary-meta">
                        <span>
                            <i class="fas fa-robot"></i>
                            AI Generated
                        </span>
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($createdAt)); ?>
                        </span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-content">
                        <?php
                        // Convert plain text to formatted HTML
                        $formattedSummary = nl2br(htmlspecialchars($summary));

                        // Enhance formatting
                        $formattedSummary = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedSummary);
                        $formattedSummary = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $formattedSummary);

                        // Convert numbered points to ordered list
                        $formattedSummary = preg_replace_callback(
                            '/(\d+\.\s+[^\n]+(\n|$))+/',
                            function ($matches) {
                                $items = preg_split('/\d+\.\s+/', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
                                $list = '<ol>';
                                foreach ($items as $item) {
                                    if (trim($item)) {
                                        $list .= '<li>' . trim($item) . '</li>';
                                    }
                                }
                                $list .= '</ol>';
                                return $list;
                            },
                            $formattedSummary
                        );

                        // Convert bullet points to unordered list
                        $formattedSummary = preg_replace_callback(
                            '/([-•]\s+[^\n]+(\n|$))+/',
                            function ($matches) {
                                $items = preg_split('/[-•]\s+/', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
                                $list = '<ul>';
                                foreach ($items as $item) {
                                    if (trim($item)) {
                                        $list .= '<li>' . trim($item) . '</li>';
                                    }
                                }
                                $list .= '</ul>';
                                return $list;
                            },
                            $formattedSummary
                        );

                        echo $formattedSummary;
                        ?>
                    </div>
                </div>

                <!-- Ask for Clarification Section -->
                <div class="clarification-section"
                    style="margin-top: 2rem; padding: 2rem; background: #F9FAFB; border-radius: 12px; border: 2px dashed #D1D5DB;">
                    <h3 style="margin: 0 0 1rem 0; color: #111827; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-question-circle" style="color: #4F46E5;"></i>
                        Need More Clarification?
                    </h3>
                    <p style="color: #6B7280; margin-bottom: 1rem;">Ask any question about this material and get a
                        detailed explanation from AI.</p>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="clarificationQuestion"
                            placeholder="E.g., Can you explain this concept in simpler terms?"
                            style="flex: 1; padding: 0.75rem; border: 2px solid #E5E7EB; border-radius: 8px; font-size: 1rem;">
                        <button onclick="askClarification()" id="askBtn" class="action-btn btn-primary"
                            style="white-space: nowrap;">
                            <i class="fas fa-paper-plane"></i>
                            Ask AI
                        </button>
                    </div>
                    <div id="clarificationResponse"
                        style="margin-top: 1rem; display: none; padding: 1.5rem; background: white; border-radius: 8px; border-left: 4px solid #4F46E5;">
                        <h4
                            style="margin: 0 0 0.75rem 0; color: #4F46E5; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-robot"></i>
                            AI Explanation:
                        </h4>
                        <div id="clarificationContent" style="color: #374151; line-height: 1.8;"></div>
                    </div>
                </div>

                <div class="action-buttons" style="margin-top: 2rem;">
                    <button onclick="window.print()" class="action-btn btn-print">
                        <i class="fas fa-print"></i>
                        Print Summary
                    </button>
                    <a href="flashcards.php?note_id=<?php echo $noteId; ?>" class="action-btn btn-secondary">
                        <i class="fas fa-clone"></i>
                        Study Flashcards
                    </a>
                    <a href="quiz.php?note_id=<?php echo $noteId; ?>" class="action-btn btn-secondary">
                        <i class="fas fa-question-circle"></i>
                        Take Quiz
                    </a>
                    <a href="materials.php" class="action-btn btn-primary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Materials
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function askClarification() {
            const questionInput = document.getElementById('clarificationQuestion');
            const askBtn = document.getElementById('askBtn');
            const responseDiv = document.getElementById('clarificationResponse');
            const contentDiv = document.getElementById('clarificationContent');

            const question = questionInput.value.trim();

            if (!question) {
                alert('Please enter a question');
                return;
            }

            // Disable button and show loading
            askBtn.disabled = true;
            askBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Answer...';
            responseDiv.style.display = 'block';
            contentDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is preparing a detailed explanation...';

            try {
                const response = await fetch('../api/get_clarification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        note_id: <?php echo $noteId; ?>,
                        question: question,
                        context: 'Asking about the summary content'
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
        document.getElementById('clarificationQuestion').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                askClarification();
            }
        });
    </script>
</body>

</html>