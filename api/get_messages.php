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

$otherUserId = intval($_GET['user_id'] ?? 0);
$currentUserId = $_SESSION['user_id'];

if ($otherUserId === 0) {
    sendJsonResponse(false, null, 'Invalid user ID');
}

try {
    // Get conversation messages
    $query = "
        SELECT 
            m.message_id,
            m.sender_id,
            m.receiver_id,
            m.message_text,
            m.is_read,
            m.sent_at,
            sender.full_name as sender_name,
            sender.role as sender_role
        FROM messages m
        JOIN users sender ON m.sender_id = sender.user_id
        WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
           OR (m.sender_id = :user2 AND m.receiver_id = :user1)
        ORDER BY m.sent_at ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user1', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':user2', $otherUserId, PDO::PARAM_INT);
    $stmt->execute();

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $markReadQuery = "UPDATE messages SET is_read = TRUE WHERE receiver_id = :current_user AND sender_id = :other_user AND is_read = FALSE";
    $markReadStmt = $conn->prepare($markReadQuery);
    $markReadStmt->bindParam(':current_user', $currentUserId, PDO::PARAM_INT);
    $markReadStmt->bindParam(':other_user', $otherUserId, PDO::PARAM_INT);
    $markReadStmt->execute();

    sendJsonResponse(true, $messages, 'Messages retrieved successfully');

} catch (PDOException $e) {
    error_log("Error getting messages: " . $e->getMessage());
    sendJsonResponse(false, null, 'Database error: ' . $e->getMessage());
}
?>