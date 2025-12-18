<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$database = new Database();
$conn = $database->getConnection();

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$currentUserId = $_SESSION['user_id'];
$currentRole = $_SESSION['role'];

if (empty($title) || empty($content)) {
    sendJsonResponse(false, null, 'Title and content are required');
}

try {
    // Get teacher ID based on role
    if ($currentRole === 'teacher') {
        $teacherId = $currentUserId;
    } else {
        // Get student's teacher
        $teacherQuery = "SELECT teacher_id FROM teacher_students WHERE student_id = :student_id LIMIT 1";
        $teacherStmt = $conn->prepare($teacherQuery);
        $teacherStmt->bindParam(':student_id', $currentUserId, PDO::PARAM_INT);
        $teacherStmt->execute();
        $teacherResult = $teacherStmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacherResult) {
            sendJsonResponse(false, null, 'You must be enrolled in a class to post discussions');
        }
        $teacherId = $teacherResult['teacher_id'];
    }

    // Insert discussion
    $query = "INSERT INTO class_discussions (teacher_id, author_id, title, content) VALUES (:teacher_id, :author_id, :title, :content)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->bindParam(':author_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->execute();

    sendJsonResponse(true, ['discussion_id' => $conn->lastInsertId()], 'Discussion posted');

} catch (PDOException $e) {
    error_log("Error posting discussion: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to post discussion');
}
?>