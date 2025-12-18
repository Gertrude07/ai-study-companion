<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/StudyMaterial.php';

requireLogin();

$userId = $_SESSION['user_id'];
$noteObj = new Note();
$materialObj = new StudyMaterial();

$notes = $noteObj->getAllByUser($userId);

$pageTitle = 'My Materials';
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
                <h2><i class="fas fa-graduation-cap"></i> Study Companion</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="upload.php" class="nav-item">
                    <i class="fas fa-upload"></i> Upload Notes
                </a>
                <a href="materials.php" class="nav-item active">
                    <i class="fas fa-book"></i> My Materials
                </a>
                <a href="messages.php" class="nav-item">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="discussions.php" class="nav-item">
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
                <div style="margin-bottom: 1rem;">
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <h1><i class="fas fa-book"></i> My Study Materials</h1>
                <p>Access your notes, summaries, flashcards, and quizzes</p>
            </header>

            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h2>No materials yet</h2>
                    <p>Upload your first note to get started with AI-powered study materials</p>
                    <a href="upload.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-upload"></i> Upload Notes
                    </a>
                </div>
            <?php else: ?>
                <div class="materials-list">
                    <?php foreach ($notes as $note):
                        $materials = $materialObj->getAllByNote($note['note_id']);
                        $hasMaterials = !empty($materials);

                        $summaryMaterial = null;
                        $flashcardMaterial = null;
                        $quizMaterial = null;

                        foreach ($materials as $material) {
                            if ($material['material_type'] === 'summary')
                                $summaryMaterial = $material;
                            if ($material['material_type'] === 'flashcard_set')
                                $flashcardMaterial = $material;
                            if ($material['material_type'] === 'quiz_set')
                                $quizMaterial = $material;
                        }
                        ?>
                        <div class="card" style="margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: start; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="material-icon" style="width: 60px; height: 60px;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;">
                                        <?php echo htmlspecialchars($note['title']); ?>
                                    </h2>
                                    <p class="material-meta">
                                        <i class="fas fa-clock"></i>
                                        Uploaded <?php echo timeAgo($note['upload_date']); ?>
                                        <span style="margin: 0 0.5rem;">•</span>
                                        <i class="fas fa-file"></i>
                                        <?php echo htmlspecialchars($note['original_filename']); ?>
                                    </p>
                                </div>
                                <div>
                                    <?php if ($hasMaterials): ?>
                                        <span class="badge"
                                            style="background: #10B981; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;">
                                            <i class="fas fa-check-circle"></i> Materials Ready
                                        </span>
                                    <?php else: ?>
                                        <span class="badge"
                                            style="background: #F59E0B; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem;">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($hasMaterials): ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <?php if ($summaryMaterial): ?>
                                        <a href="summary.php?note_id=<?php echo $note['note_id']; ?>" class="action-btn"
                                            style="text-decoration: none;">
                                            <i class="fas fa-file-alt" style="color: #3B82F6;"></i>
                                            <span>View Summary</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($flashcardMaterial): ?>
                                        <a href="flashcards.php?note_id=<?php echo $note['note_id']; ?>" class="action-btn"
                                            style="text-decoration: none;">
                                            <i class="fas fa-layer-group" style="color: #10B981;"></i>
                                            <span>Study Flashcards</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($quizMaterial): ?>
                                        <a href="quiz.php?note_id=<?php echo $note['note_id']; ?>" class="action-btn"
                                            style="text-decoration: none;">
                                            <i class="fas fa-clipboard-check" style="color: #F59E0B;"></i>
                                            <span>Take Quiz</span>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                    <button onclick="regenerateMaterials(<?php echo $note['note_id']; ?>)" class="btn btn-secondary"
                                        id="regenerateBtn<?php echo $note['note_id']; ?>">
                                        <i class="fas fa-sync-alt"></i> Regenerate All Materials
                                    </button>
                                    <button onclick="deleteNote(<?php echo $note['note_id']; ?>)" class="btn"
                                        style="background: #EF4444; color: white;">
                                        <i class="fas fa-trash"></i> Delete Note
                                    </button>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                    <button onclick="generateMaterials(<?php echo $note['note_id']; ?>)" class="btn btn-primary"
                                        id="generateBtn<?php echo $note['note_id']; ?>">
                                        <i class="fas fa-magic"></i> Generate Study Materials
                                    </button>
                                    <button onclick="deleteNote(<?php echo $note['note_id']; ?>)" class="btn"
                                        style="background: #EF4444; color: white;">
                                        <i class="fas fa-trash"></i> Delete Note
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        async function generateMaterials(noteId) {
            const btn = document.getElementById('generateBtn' + noteId);
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

            try {
                const formData = new FormData();
                formData.append('note_id', noteId);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/generate_materials.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Materials generated successfully!');
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'Failed to generate materials'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-magic"></i> Generate Study Materials';
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Generate Study Materials';
            }
        }

        async function regenerateMaterials(noteId) {
            if (!confirm('This will delete existing materials and generate new ones.\n\nThis process will take ~1 minute:\n• Summary (~30 sec)\n• Flashcards (~20 sec)\n• Quiz (~20 sec)\n\nContinue?')) {
                return;
            }

            const btn = document.getElementById('regenerateBtn' + noteId);
            btn.disabled = true;

            // Show progress modal
            const progressModal = document.createElement('div');
            progressModal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 9999;';
            progressModal.innerHTML = `
                <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
                    <h3 style="margin: 0 0 1rem 0; color: #000 !important; font-weight: 600;"><i class="fas fa-magic"></i> Generating Study Materials</h3>
                    <div id="progress-steps" style="margin-bottom: 1rem;">
                        <div class="progress-step" data-step="summary" style="color: #000 !important; font-weight: 500;"><i class="fas fa-circle-notch fa-spin"></i> Generating summary...</div>
                        <div class="progress-step" data-step="flashcards" style="opacity: 0.5; color: #000 !important; font-weight: 500;"><i class="fas fa-circle"></i> Flashcards (pending)</div>
                        <div class="progress-step" data-step="quiz" style="opacity: 0.5; color: #000 !important; font-weight: 500;"><i class="fas fa-circle"></i> Quiz questions (pending)</div>
                    </div>
                    <div style="text-align: center; color: #000 !important; font-size: 0.9rem; font-weight: 500;">
                        <i class="fas fa-clock"></i> Estimated time: ~1 minute<br>
                        <small style="color: #111 !important; font-weight: 400;">Use "Generate More" buttons to create additional sets later</small>
                    </div>
                </div>
            `;
            document.body.appendChild(progressModal);

            // Simulate progress updates (every 20 seconds)
            let currentStep = 0;
            const steps = ['summary', 'flashcards', 'quiz'];
            const progressInterval = setInterval(() => {
                if (currentStep < steps.length) {
                    const stepEl = document.querySelector(`[data-step="${steps[currentStep]}"]`);
                    if (stepEl) {
                        stepEl.style.opacity = '1';
                        stepEl.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${stepEl.textContent.replace(' (pending)', '...')}`;
                    }
                    if (currentStep > 0) {
                        const prevStepEl = document.querySelector(`[data-step="${steps[currentStep - 1]}"]`);
                        if (prevStepEl) {
                            prevStepEl.innerHTML = `<i class="fas fa-check-circle" style="color: #10B981;"></i> ${prevStepEl.textContent.replace('...', ' (complete)')}`;
                        }
                    }
                    currentStep++;
                }
            }, 20000); // Update every 20 seconds

            try {
                const formData = new FormData();
                formData.append('note_id', noteId);
                formData.append('regenerate', 'true');
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/generate_materials.php', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    clearInterval(progressInterval);
                    document.body.removeChild(progressModal);
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Received invalid response from server. Check console for details.');
                    btn.disabled = false;
                    return;
                }

                clearInterval(progressInterval);
                document.body.removeChild(progressModal);

                if (result.success) {
                    alert('✅ Materials generated successfully!\n\n' +
                        'Summary: ' + (result.data.summary ? '✓' : '✗') + '\n' +
                        'Flashcards: ' + (result.data.flashcards ? '✓ 15 cards' : '✗') + '\n' +
                        'Quiz: ' + (result.data.quiz ? '✓ 10 questions' : '✗') + '\n\n' +
                        'Use "Generate More" buttons to create additional sets!');
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'Failed to regenerate materials'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Regenerate Materials';
                }
            } catch (error) {
                clearInterval(progressInterval);
                document.body.removeChild(progressModal);
                alert('❌ Error: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Regenerate Materials';
            }
        }

        async function deleteNote(noteId) {
            // First confirmation
            if (!confirm('⚠️ WARNING: This will permanently delete:\n\n' +
                '• The uploaded note file\n' +
                '• All generated summaries\n' +
                '• All flashcard sets\n' +
                '• All quiz questions\n' +
                '• All quiz attempts and results\n\n' +
                'This action CANNOT be undone!\n\n' +
                'Do you want to continue?')) {
                return;
            }

            // Second confirmation
            if (!confirm('⚠️ FINAL CONFIRMATION\n\n' +
                'Are you ABSOLUTELY SURE you want to delete this note and ALL its materials?\n\n' +
                'Click OK to proceed with deletion.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('note_id', noteId);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/delete_note.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'Failed to delete note'));
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }
    </script>
</body>

</html>