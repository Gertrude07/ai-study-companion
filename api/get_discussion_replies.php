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

$database = new Database();
$conn = $database->getConnection();
$discussionId = intval($_GET['discussion_id'] ?? 0);

if ($discussionId === 0) {
    sendJsonResponse(false, null, 'Invalid discussion ID');
}

try {
    // Get all replies for discussion
    $query = "
        SELECT 
            r.reply_id,
            r.content,
            r.created_at,
            u.full_name as author_name,
            u.role as author_role
        FROM discussion_replies r
        JOIN users u ON r.author_id = u.user_id
        WHERE r.discussion_id = :discussion_id
        ORDER BY r.created_at ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':discussion_id', $discussionId, PDO::PARAM_INT);
    $stmt->execute();

    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(true, $replies, 'Replies retrieved successfully');

} catch (PDOException $e) {
    error_log("Error getting replies: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to retrieve replies');
}
?>