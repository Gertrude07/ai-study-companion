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
$currentUserId = $_SESSION['user_id'];

try {
    // Get all conversations with last message and unread count
    $query = "
        SELECT 
            CASE 
                WHEN m.sender_id = :current_user THEN m.receiver_id 
                ELSE m.sender_id 
            END as other_user_id,
            u.full_name as other_user_name,
            u.role as other_user_role,
            MAX(m.sent_at) as last_message_time,
            (SELECT message_text FROM messages 
             WHERE (sender_id = other_user_id AND receiver_id = :current_user2)
                OR (sender_id = :current_user3 AND receiver_id = other_user_id)
             ORDER BY sent_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM messages 
             WHERE receiver_id = :current_user4 AND sender_id = other_user_id AND is_read = FALSE) as unread_count
        FROM messages m
        JOIN users u ON (
            CASE 
                WHEN m.sender_id = :current_user5 THEN m.receiver_id 
                ELSE m.sender_id 
            END = u.user_id
        )
        WHERE m.sender_id = :current_user6 OR m.receiver_id = :current_user7
        GROUP BY other_user_id
        ORDER BY last_message_time DESC
    ";

    $stmt = $conn->prepare($query);
    for ($i = 1; $i <= 7; $i++) {
        $param = ':current_user' . ($i > 1 ? $i : '');
        $stmt->bindParam($param, $currentUserId, PDO::PARAM_INT);
    }
    $stmt->execute();

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(true, $conversations, 'Conversations retrieved');

} catch (PDOException $e) {
    error_log("Error getting conversations: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to retrieve conversations');
}
?>