<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Upload Notes';
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
                <a href="upload.php" class="nav-item active">
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
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <a href="index.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <h1><i class="fas fa-upload"></i> Upload Study Notes</h1>
                <p>Upload your notes and let AI generate study materials</p>
            </header>

            <div class="card" style="max-width: 700px;">
                <div id="message-container"></div>

                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label for="title"><i class="fas fa-heading"></i> Note Title *</label>
                        <input type="text" id="title" name="title" placeholder="e.g., Biology Chapter 5: Photosynthesis"
                            required>
                        <span class="error-text" id="titleError"></span>
                    </div>

                    <div class="form-group">
                        <label for="noteFile"><i class="fas fa-file"></i> Upload File *</label>
                        <input type="file" id="noteFile" name="note_file" accept=".pdf,.docx,.txt" required>
                        <small class="form-hint">Supported formats: PDF, DOCX, TXT - Max 10MB</small>
                        <span class="error-text" id="fileError"></span>
                    </div>

                    <div id="uploadProgress" style="display: none; margin-bottom: 1rem;">
                        <div style="background: #E5E7EB; border-radius: 8px; height: 12px; overflow: hidden;">
                            <div id="progressBar"
                                style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s;">
                            </div>
                        </div>
                        <p id="progressText"
                            style="text-align: center; margin-top: 0.5rem; color: #6B7280; font-size: 0.9rem;"></p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                        <i class="fas fa-upload"></i> Upload and Generate Materials
                    </button>
                </form>

                <div class="info-message" style="margin-top: 2rem;">
                    <i class="fas fa-info-circle"></i>
                    <strong>What happens next?</strong>
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                        <li>Your file will be uploaded and processed</li>
                        <li>AI will extract text from your document</li>
                        <li>Study materials will be generated automatically</li>
                        <li>You can then view summaries, flashcards, and quizzes</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/validation.js"></script>
    <script src="../assets/js/ajax-handler.js"></script>
    <script>
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const messageContainer = document.getElementById('message-container');

        uploadForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Clear previous messages
            messageContainer.innerHTML = '';
            document.querySelectorAll('.error-text').forEach(el => el.textContent = '');

            // Get form data
            const title = document.getElementById('title').value.trim();
            const fileInput = document.getElementById('noteFile');
            const file = fileInput.files[0];

            // Validate
            let isValid = true;

            if (title.length < 3) {
                document.getElementById('titleError').textContent = 'Title must be at least 3 characters';
                isValid = false;
            }

            if (!file) {
                document.getElementById('fileError').textContent = 'Please select a file';
                isValid = false;
            } else {
                // Check file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    document.getElementById('fileError').textContent = 'File size must be less than 10MB';
                    isValid = false;
                }

                // Check file type
                const allowedTypes = [
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'text/plain'
                ];
                const allowedExtensions = /\.(pdf|docx|txt)$/i;

                if (!allowedTypes.includes(file.type) && !file.name.match(allowedExtensions)) {
                    document.getElementById('fileError').textContent = 'Only PDF, DOCX, and TXT files are allowed';
                    isValid = false;
                }
            }

            if (!isValid) return;

            // Prepare form data
            const formData = new FormData(uploadForm);

            // Show progress
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            progressDiv.style.display = 'block';

            // Upload with progress
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.textContent = `Uploading... ${percent}%`;
                }
            });

            xhr.addEventListener('load', function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            progressText.textContent = 'Upload complete! Generating materials...';

                            // Now generate materials
                            generateMaterials(response.data.note_id);
                        } else {
                            showMessage(response.message || 'Upload failed', 'error');
                            resetForm();
                        }
                    } catch (error) {
                        showMessage('An error occurred processing the response', 'error');
                        resetForm();
                    }
                } else {
                    showMessage('Upload failed. Please try again.', 'error');
                    resetForm();
                }
            });

            xhr.addEventListener('error', function () {
                showMessage('Network error. Please check your connection.', 'error');
                resetForm();
            });

            xhr.open('POST', '../api/upload_note.php');
            xhr.send(formData);
        });

        async function generateMaterials(noteId) {
            try {
                const formData = new FormData();
                formData.append('note_id', noteId);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                progressBar.style.width = '100%';

                // Show detailed progress
                let step = 0;
                const steps = [
                    'Generating summary...',
                    'Creating flashcards...',
                    'Generating quiz questions...'
                ];

                const progressInterval = setInterval(() => {
                    if (step < steps.length) {
                        progressText.textContent = steps[step] + ' (' + Math.round(((step + 1) / steps.length) * 100) + '% complete)';
                        step++;
                    }
                }, 20000); // Update every 20 seconds

                progressText.textContent = steps[0] + ' (Est. 1 minute total)';

                const response = await fetch('../api/generate_materials.php', {
                    method: 'POST',
                    body: formData
                });

                clearInterval(progressInterval);
                const result = await response.json();

                if (result.success) {
                    progressText.textContent = 'All materials generated successfully!';
                    showMessage('✅ Success! Study materials generated. Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'materials.php?note_id=' + noteId;
                    }, 2000);
                } else {
                    showMessage(result.message || 'Failed to generate materials', 'warning');
                    setTimeout(() => {
                        window.location.href = 'materials.php';
                    }, 3000);
                }
            } catch (error) {
                showMessage('Error generating materials: ' + error.message, 'error');
                resetForm();
            }
        }

        function showMessage(message, type) {
            const className = type === 'error' ? 'error-message' :
                type === 'success' ? 'success-message' :
                    type === 'warning' ? 'warning-message' : 'info-message';

            messageContainer.innerHTML = `<div class="${className}">${message}</div>`;
        }

        function resetForm() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload and Generate Materials';
            progressDiv.style.display = 'none';
            progressBar.style.width = '0%';
        }
    </script>
</body>

</html>