<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../dashboard/index.php');
    exit();
}

$pageTitle = 'Sign Up';
$cssPath = '../assets/css/';
$additionalCSS = [];

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p>Join AI Study Companion and supercharge your learning</p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>

        <form id="signupForm" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-group">
                <label for="fullName"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="fullName" name="full_name" placeholder="John Doe" required>
                <span class="error-text" id="nameError"></span>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" placeholder="john@example.com" required>
                <span class="error-text" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <span class="error-text" id="passwordError"></span>
                <small class="form-hint">At least 8 characters, 1 letter and 1 number</small>
            </div>

            <div class="form-group">
                <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="••••••••" required>
                <span class="error-text" id="confirmPasswordError"></span>
            </div>

            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> I am a...</label>
                <select id="role" name="role" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher/Instructor</option>
                </select>
                <small class="form-hint">Select your role in the system</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Sign Up
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Log in</a></p>
        </div>
    </div>
</div>

<script src="../assets/js/validation.js"></script>
<script>
    document.getElementById('signupForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Clear previous errors
        clearErrors();

        // Get form data
        const fullName = document.getElementById('fullName').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validate
        let isValid = true;

        if (!validateName(fullName)) {
            showError('nameError', 'Name must be 2-50 characters, letters only');
            isValid = false;
        }

        if (!validateEmail(email)) {
            showError('emailError', 'Please enter a valid email address');
            isValid = false;
        }

        if (!validatePassword(password)) {
            showError('passwordError', 'Password must be at least 8 characters with 1 letter and 1 number');
            isValid = false;
        }

        if (password !== confirmPassword) {
            showError('confirmPasswordError', 'Passwords do not match');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // Submit form
        try {
            const formData = new FormData(this);
            const response = await fetch('process_auth.php?action=signup', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showSuccess('Registration successful! Redirecting to login...');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            showMessage('An error occurred. Please try again.', 'error');
        }
    });

    function showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function clearErrors() {
        const errors = document.querySelectorAll('.error-text');
        errors.forEach(error => {
            error.textContent = '';
            error.style.display = 'none';
        });
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('successMessage').style.display = 'none';
    }

    function showMessage(message, type) {
        const messageDiv = type === 'error' ? document.getElementById('errorMessage') : document.getElementById('successMessage');
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';
    }

    function showSuccess(message) {
        showMessage(message, 'success');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>