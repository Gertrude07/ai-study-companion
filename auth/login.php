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

$pageTitle = 'Login';
$cssPath = '../assets/css/';
$additionalCSS = [];

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1><i class="fas fa-sign-in-alt"></i> Welcome Back</h1>
            <p>Log in to continue your learning journey</p>
        </div>

        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>

        <form id="loginForm" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" placeholder="john@example.com" required>
                <span class="error-text" id="emailError"></span>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <span class="error-text" id="passwordError"></span>
            </div>

            <div class="form-group" style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                <input type="checkbox" id="remember" name="remember" value="true"
                    style="width: auto; margin-right: 0.5rem;">
                <label for="remember" style="margin-bottom: 0;">Remember me for 30 days</label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Log In
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
        </div>
    </div>
</div>

<script src="../assets/js/validation.js"></script>
<script>
    document.getElementById('loginForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        // Clear previous errors
        clearErrors();

        // Get form data
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        // Validate
        let isValid = true;

        if (!validateEmail(email)) {
            showError('emailError', 'Please enter a valid email address');
            isValid = false;
        }

        if (password.length === 0) {
            showError('passwordError', 'Please enter your password');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        // Submit form
        try {
            const formData = new FormData(this);
            const response = await fetch('process_auth.php?action=login', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showSuccess('Login successful! Redirecting...');
                setTimeout(() => {
                    // Redirect based on user role
                    const userRole = result.data.role || 'student';
                    if (userRole === 'teacher') {
                        window.location.href = '../dashboard/teacher/index.php';
                    } else {
                        window.location.href = '../dashboard/index.php';
                    }
                }, 1000);
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