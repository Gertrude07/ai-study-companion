<?php
// Join Class API Endpoint - Handles student enrollment

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Teacher.php';

requireLogin();
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

// Check if user is a student
if ($_SESSION['role'] !== 'student') {
    sendJsonResponse(false, null, 'Only students can join classes');
}

// Get and sanitize input
$classCode = sanitizeInput($_POST['class_code'] ?? '');

// Validate input
if (empty($classCode)) {
    sendJsonResponse(false, null, 'Class code is required');
}

// Enroll student
$teacherObj = new Teacher();
$result = $teacherObj->enrollStudent($_SESSION['user_id'], strtoupper($classCode));

sendJsonResponse($result['success'], $result, $result['message']);
?>