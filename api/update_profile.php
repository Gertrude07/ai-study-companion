<?php
// Prevent any output before JSON
ini_set('display_errors', 0);
error_reporting(0);

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Clear any output buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$userId = $_SESSION['user_id'];
$updateType = $_POST['update_type'] ?? '';



try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User();

    if ($updateType === 'username') {
        // Update username
        $newUsername = trim($_POST['new_username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';

        if (empty($newUsername)) {
            echo json_encode(['success' => false, 'message' => 'Username cannot be empty']);
            exit;
        }

        if (strlen($newUsername) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
            exit;
        }

        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required']);
            exit;
        }

        // Verify current password
        $email = $_SESSION['email'];
        $loginResult = $user->login($email, $currentPassword);
        if (!$loginResult['success']) {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
            exit;
        }

        // Update username
        $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        if ($stmt->execute([$newUsername, $userId])) {
            $_SESSION['full_name'] = $newUsername;
            echo json_encode([
                'success' => true,
                'message' => 'Username updated successfully',
                'new_username' => $newUsername
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update username']);
        }

    } elseif ($updateType === 'password') {
        // Update password
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required']);
            exit;
        }

        if (empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'New password is required']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }

        // Verify current password
        $email = $_SESSION['email'];
        $loginResult = $user->login($email, $currentPassword);
        if (!$loginResult['success']) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        if ($stmt->execute([$hashedPassword, $userId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid update type']);
    }

} catch (Exception $e) {
    error_log("Update profile error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile']);
}

// Flush output buffer
ob_end_flush();
