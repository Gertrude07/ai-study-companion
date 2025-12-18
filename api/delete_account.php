<?php
// Delete User Account and All Related Data

// Start output buffering to prevent any accidental output
ob_start();

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

// Clean any output buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$userId = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';

if (empty($password)) {
    sendJsonResponse(false, null, 'Password is required to delete your account');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJsonResponse(false, null, 'Invalid security token');
}

try {
    $userObj = new User();

    // Verify password before deletion
    $email = $_SESSION['email'];
    $loginResult = $userObj->login($email, $password);

    if (!$loginResult['success']) {
        sendJsonResponse(false, null, 'Incorrect password. Account deletion cancelled.');
    }

    // Delete user account and all related data
    $result = $userObj->deleteAccount($userId);

    if ($result['success']) {
        // Destroy session
        session_destroy();
        sendJsonResponse(true, null, 'Your account and all data have been permanently deleted.');
    } else {
        sendJsonResponse(false, null, $result['message'] ?? 'Failed to delete account');
    }

} catch (Exception $e) {
    error_log("Delete account error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error deleting account: ' . $e->getMessage());
}
?>