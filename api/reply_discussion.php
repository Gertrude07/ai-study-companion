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

$discussionId = intval($_POST['discussion_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$currentUserId = $_SESSION['user_id'];

if ($discussionId === 0 || empty($content)) {
    sendJsonResponse(false, null, 'Invalid input');
}

try {
    // Verify discussion exists and user has access
    $checkQuery = "SELECT d.discussion_id 
                FROM class_discussions d
                LEFT JOIN teacher_students ts ON d.teacher_id = ts.teacher_id
                WHERE d.discussion_id = :discussion_id 
                AND (d.teacher_id = :user_id OR ts.student_id = :user_id2 OR d.author_id = :user_id3)";

    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':discussion_id', $discussionId, PDO::PARAM_INT);
    $checkStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $checkStmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
    $checkStmt->bindParam(':user_id3', $currentUserId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'Discussion not found or access denied');
    }

    // Insert reply
    $query = "INSERT INTO discussion_replies (discussion_id, author_id, content) VALUES (:discussion_id, :author_id, :content)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':discussion_id', $discussionId, PDO::PARAM_INT);
    $stmt->bindParam(':author_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->execute();

    // Update discussion timestamp
    $updateQuery = "UPDATE class_discussions SET updated_at = CURRENT_TIMESTAMP WHERE discussion_id = :discussion_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':discussion_id', $discussionId, PDO::PARAM_INT);
    $updateStmt->execute();

    sendJsonResponse(true, ['reply_id' => $conn->lastInsertId()], 'Reply posted');

} catch (PDOException $e) {
    error_log("Error posting reply: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to post reply');
}
?>