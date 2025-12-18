<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$pageTitle = 'Account Settings';
$userName = $_SESSION['full_name'];
$userEmail = $_SESSION['email'];
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
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .settings-section h2 {
            font-size: 1.25rem;
            color: #111827;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .danger-zone {
            border: 2px solid #EF4444;
            background: #FEE2E2;
        }

        .danger-zone h2 {
            color: #DC2626;
        }

        .info-text {
            color: #6B7280;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #F9FAFB;
            border-radius: 8px;
        }

        .info-item i {
            color: #6B7280;
        }

        .info-item strong {
            min-width: 100px;
            color: #374151;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3B82F6;
        }

        .error-message {
            display: block;
            color: #EF4444;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            min-height: 1.25rem;
        }

        .success-message {
            display: block;
            color: #10B981;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        /* Theme Selector Styles */
        .theme-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .theme-option {
            position: relative;
            cursor: pointer;
            display: block;
        }

        .theme-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .theme-preview {
            border: 3px solid transparent;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .theme-option input[type="radio"]:checked+.theme-preview {
            border-color: #0891B2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
            transform: translateY(-2px);
        }

        .theme-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .dark-preview {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: #FFFFFF;
        }

        .light-preview {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F7FA 100%);
            color: #1A202C;
            border: 1px solid #E5E7EB;
        }

        .theme-preview .theme-name {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-preview .theme-description {
            font-size: 0.875rem;
            opacity: 0.8;
            line-height: 1.5;
        }

        .theme-preview .theme-colors {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .theme-color-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .dark-preview .color-1 {
            background: #0891B2;
        }

        .dark-preview .color-2 {
            background: #06B6D4;
        }

        .dark-preview .color-3 {
            background: #000000;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .light-preview .color-1 {
            background: #2196F3;
        }

        .light-preview .color-2 {
            background: #64B5F6;
        }

        .light-preview .color-3 {
            background: #FFFFFF;
            border-color: rgba(0, 0, 0, 0.2);
        }

        .theme-check {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 28px;
            height: 28px;
            background: #10B981;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }

        .theme-option input[type="radio"]:checked~.theme-check {
            display: flex;
        }
    </style>
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
                <a href="settings.php" class="nav-item active">
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
                <h1><i class="fas fa-cog"></i> Account Settings</h1>
                <p>Manage your account preferences and data</p>
            </header>

            <div class="settings-container">
                <!-- Theme Settings -->
                <div class="settings-section">
                    <h2><i class="fas fa-palette"></i> Theme Preferences</h2>
                    <p class="info-text">Choose your preferred theme</p>
                    <div class="theme-selector">
                        <label class="theme-option">
                            <input type="radio" name="theme" value="dark" id="themeDark" checked>
                            <div class="theme-preview dark-preview">
                                <div class="theme-name">
                                    <i class="fas fa-moon"></i>
                                    <span>Dark Theme</span>
                                </div>
                                <div class="theme-description">
                                    Sea blue accents with black background
                                </div>
                                <div class="theme-colors">
                                    <div class="theme-color-dot color-1"></div>
                                    <div class="theme-color-dot color-2"></div>
                                    <div class="theme-color-dot color-3"></div>
                                </div>
                            </div>
                            <span class="theme-check"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="theme-option">
                            <input type="radio" name="theme" value="light" id="themeLight">
                            <div class="theme-preview light-preview">
                                <div class="theme-name">
                                    <i class="fas fa-sun"></i>
                                    <span>Light Theme</span>
                                </div>
                                <div class="theme-description">
                                    Sky blue with clean white backgrounds
                                </div>
                                <div class="theme-colors">
                                    <div class="theme-color-dot color-1"></div>
                                    <div class="theme-color-dot color-2"></div>
                                    <div class="theme-color-dot color-3"></div>
                                </div>
                            </div>
                            <span class="theme-check"><i class="fas fa-check"></i></span>
                        </label>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="settings-section">
                    <h2><i class="fas fa-user"></i> Account Information</h2>
                    <div class="user-info">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <strong>Name:</strong>
                            <span id="displayName"
                                style="color: #000 !important;"><?php echo htmlspecialchars($userName); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <strong>Email:</strong>
                            <span style="color: #000 !important;"><?php echo htmlspecialchars($userEmail); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Change Username -->
                <div class="settings-section">
                    <h2><i class="fas fa-user-edit"></i> Change Username</h2>
                    <p class="info-text">Update your display name</p>
                    <form id="usernameForm" onsubmit="updateUsername(event)">
                        <div class="form-group">
                            <label for="newUsername">New Username</label>
                            <input type="text" id="newUsername" name="new_username" placeholder="Enter new username"
                                required minlength="3">
                        </div>
                        <div class="form-group">
                            <label for="usernamePassword">Current Password</label>
                            <input type="password" id="usernamePassword" name="current_password"
                                placeholder="Confirm with your password" required>
                        </div>
                        <span id="usernameError" class="error-message"></span>
                        <button type="submit" class="btn btn-primary" id="usernameBtn">
                            <i class="fas fa-save"></i> Update Username
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-section">
                    <h2><i class="fas fa-key"></i> Change Password</h2>
                    <p class="info-text">Ensure your account stays secure by using a strong password</p>
                    <form id="passwordForm" onsubmit="updatePassword(event)">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="current_password"
                                placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="new_password" placeholder="Enter new password"
                                required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" name="confirm_password"
                                placeholder="Confirm new password" required minlength="6">
                        </div>
                        <span id="passwordError" class="error-message"></span>
                        <button type="submit" class="btn btn-primary" id="passwordBtn">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="settings-section danger-zone">
                    <h2><i class="fas fa-exclamation-triangle"></i> Danger Zone</h2>
                    <p class="info-text">
                        <strong>Delete Your Account:</strong><br>
                        Once you delete your account, there is no going back. This will permanently delete:
                    </p>
                    <ul class="info-text" style="margin-left: 1.5rem;">
                        <li>Your user account</li>
                        <li>All uploaded notes and files</li>
                        <li>All generated summaries</li>
                        <li>All flashcard sets</li>
                        <li>All quiz questions and attempts</li>
                        <li>All progress and analytics data</li>
                    </ul>
                    <button onclick="showDeleteAccountModal()" class="btn" style="background: #DC2626; color: white;">
                        <i class="fas fa-trash"></i> Delete My Account
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteAccountModal"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
            <h2 style="color: #DC2626; margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Confirm Account Deletion
            </h2>
            <p style="color: #374151; margin-bottom: 1.5rem; line-height: 1.6;">
                This action is <strong>permanent and irreversible</strong>. All your data will be permanently deleted
                from our servers.
            </p>
            <div class="form-group">
                <label for="confirmPassword" style="font-weight: 600; color: #111827;">
                    Enter your password to confirm:
                </label>
                <input type="password" id="confirmPassword" placeholder="Your password"
                    style="width: 100%; padding: 0.75rem; border: 2px solid #E5E7EB; border-radius: 8px; margin-top: 0.5rem;">
                <span id="passwordError"
                    style="color: #EF4444; font-size: 0.875rem; display: none; margin-top: 0.5rem;"></span>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button onclick="hideDeleteAccountModal()" class="btn btn-secondary" style="flex: 1;">
                    Cancel
                </button>
                <button onclick="confirmDeleteAccount()" id="deleteAccountBtn" class="btn"
                    style="flex: 1; background: #DC2626; color: white;">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>
    </div>

    <script>
        async function updateUsername(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_type', 'username');
            formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

            const errorEl = document.getElementById('usernameError');
            const btn = document.getElementById('usernameBtn');

            errorEl.textContent = '';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            try {
                const response = await fetch('../api/update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                let result;

                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid server response. Please check the console for details.');
                }

                if (result.success) {
                    errorEl.className = 'success-message';
                    errorEl.textContent = '✅ ' + result.message;
                    document.getElementById('displayName').textContent = result.new_username;
                    form.reset();
                } else {
                    errorEl.className = 'error-message';
                    errorEl.textContent = '❌ ' + result.message;
                }
            } catch (error) {
                errorEl.className = 'error-message';
                errorEl.textContent = '❌ Error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update Username';
            }
        }

        async function updatePassword(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_type', 'password');
            formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

            const errorEl = document.getElementById('passwordError');
            const btn = document.getElementById('passwordBtn');

            errorEl.textContent = '';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            try {
                const response = await fetch('../api/update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    errorEl.className = 'success-message';
                    errorEl.textContent = '✅ ' + result.message;
                    form.reset();
                } else {
                    errorEl.className = 'error-message';
                    errorEl.textContent = '❌ ' + result.message;
                }
            } catch (error) {
                errorEl.className = 'error-message';
                errorEl.textContent = '❌ Error: ' + error.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update Password';
            }
        }

        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').style.display = 'flex';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
        }

        function hideDeleteAccountModal() {
            document.getElementById('deleteAccountModal').style.display = 'none';
        }

        async function confirmDeleteAccount() {
            const password = document.getElementById('confirmPassword').value;
            const errorEl = document.getElementById('passwordError');
            const btn = document.getElementById('deleteAccountBtn');

            if (!password) {
                errorEl.textContent = 'Password is required';
                errorEl.style.display = 'block';
                return;
            }

            // Final confirmation
            if (!confirm('⚠️ FINAL WARNING\n\nThis will PERMANENTLY DELETE your account and ALL data.\n\nThis action CANNOT be undone!\n\nClick OK to proceed.')) {
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            try {
                const formData = new FormData();
                formData.append('password', password);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

                const response = await fetch('../api/delete_account.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.href = '../index.php';
                } else {
                    errorEl.textContent = result.message;
                    errorEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash"></i> Delete Account';
                }
            } catch (error) {
                errorEl.textContent = 'Error: ' + error.message;
                errorEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> Delete Account';
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                hideDeleteAccountModal();
            }
        });

        // Theme Switcher Functionality
        const themeRadios = document.querySelectorAll('input[name="theme"]');

        // Load saved theme on page load
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.setAttribute('data-theme', savedTheme);

            // Update radio button
            const radio = document.querySelector(`input[name="theme"][value="${savedTheme}"]`);
            if (radio) radio.checked = true;
        }

        // Save and apply theme
        function switchTheme(theme) {
            localStorage.setItem('theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
            document.body.setAttribute('data-theme', theme);

            // Show success message
            const successMsg = document.createElement('div');
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 15px 25px; background: #10B981; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 10000; font-weight: 600; animation: slideIn 0.3s ease;';
            successMsg.innerHTML = '<i class="fas fa-check-circle"></i> Theme changed to ' + theme + ' mode';
            document.body.appendChild(successMsg);

            setTimeout(() => {
                successMsg.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => successMsg.remove(), 300);
            }, 2000);
        }

        // Add change listeners
        themeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.checked) {
                    switchTheme(e.target.value);
                }
            });
        });

        // Load theme on page load
        loadTheme();
    </script>

    <style>
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</body>

</html>