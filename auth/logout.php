<?php
session_start();

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Remove remember token from DB if logged in
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../classes/User.php';
    require_once __DIR__ . '/../config/database.php';
    $user = new User();
    $user->removeRememberToken($_SESSION['user_id']);
}

// Destroy session
session_destroy();

// Delete remember cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to landing page
header('Location: ../index.php');
exit();
?>