<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

// Get action
$action = $_GET['action'] ?? '';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJsonResponse(false, null, 'Invalid security token');
}

$user = new User();

switch ($action) {
    case 'signup':
        handleSignup($user);
        break;

    case 'login':
        handleLogin($user);
        break;

    default:
        sendJsonResponse(false, null, 'Invalid action');
}

// Handle user registration
function handleSignup($user)
{
    // Get and sanitize input
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? 'student');

    // Validate input
    if (empty($fullName) || empty($email) || empty($password)) {
        sendJsonResponse(false, null, 'All fields are required');
    }

    // Validate role
    if (!in_array($role, ['student', 'teacher'])) {
        $role = 'student'; // Default to student if invalid
    }

    // Validate name format
    if (!validateName($fullName)) {
        sendJsonResponse(false, null, 'Invalid name format. Use 2-50 letters only');
    }

    // Validate email
    if (!validateEmail($email)) {
        sendJsonResponse(false, null, 'Invalid email address');
    }

    // Validate password
    if (!validatePassword($password)) {
        sendJsonResponse(false, null, 'Password must be at least 8 characters with 1 letter and 1 number');
    }

    // Check password confirmation
    if ($password !== $confirmPassword) {
        sendJsonResponse(false, null, 'Passwords do not match');
    }

    // Register user with role
    $result = $user->register($fullName, $email, $password, $role);

    if ($result['success']) {
        // Don't auto-login - user must log in manually
        sendJsonResponse(true, ['user_id' => $result['user_id']], 'Registration successful! Please log in to continue.');
    } else {
        sendJsonResponse(false, null, $result['message']);
    }
}

// Handle user login
function handleLogin($user)
{
    // Get and sanitize input
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        sendJsonResponse(false, null, 'Email and password are required');
    }

    // Validate email format
    if (!validateEmail($email)) {
        sendJsonResponse(false, null, 'Invalid email address');
    }

    // Authenticate user
    $result = $user->login($email, $password);

    if ($result['success']) {
        // Set session including role
        $_SESSION['user_id'] = $result['user']['user_id'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['email'] = $result['user']['email'];
        $_SESSION['role'] = $result['user']['role'] ?? 'student';

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Handle Remember Me
        if (isset($_POST['remember']) && $_POST['remember'] === 'true') {
            $token = $user->generateRememberToken();
            if ($user->storeRememberToken($_SESSION['user_id'], $token)) {
                // Set cookie for 30 days
                setcookie('remember_token', $token, [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
        }

        sendJsonResponse(true, $result['user'], 'Login successful');
    } else {
        sendJsonResponse(false, null, $result['message']);
    }
}
?>