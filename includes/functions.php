<?php
// Global helper functions

// Set timezone - Change this to your local timezone
date_default_timezone_set('UTC'); // You can change to 'Africa/Accra' or your timezone

// Sanitize input data
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email format
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength (min 8 chars, 1 letter, 1 number)
function validatePassword($password)
{
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/', $password);
}

// Validate name format (letters/spaces, 2-50 chars)
function validateName($name)
{
    return preg_match('/^[a-zA-Z\s]{2,50}$/', $name);
}

// Check if user is logged in
function isLoggedIn()
{
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }

    // Check remember me cookie
    return checkRememberMe();
}

// Redirect to login if not authenticated
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Check remember me cookie
function checkRememberMe()
{
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }

    require_once __DIR__ . '/../classes/User.php';
    $userObj = new User();
    $user = $userObj->verifyRememberToken($_COOKIE['remember_token']);

    if ($user) {
        // Set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return true;
    }

    return false;
}

// Generate CSRF token
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format file size
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Format time duration
function formatDuration($seconds)
{
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}

// Get time ago string
function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Send JSON response
function sendJsonResponse($success, $data = null, $message = '')
{
    // Clear any output that may have been generated
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit();
}

// Get allowed file extensions
function getAllowedFileExtensions()
{
    return ['pdf', 'docx', 'txt'];
}

// Get max upload file size in bytes
function getMaxUploadSize()
{
    return 10 * 1024 * 1024; // 10MB
}
?>