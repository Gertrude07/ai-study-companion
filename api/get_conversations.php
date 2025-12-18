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
    // Uses a subquery to first find the latest interaction with each user
    $query = "
        SELECT 
            u.user_id as other_user_id,
            u.full_name as other_user_name,
            u.role as other_user_role,
            last_msg.last_message_time,
            
            -- Get the actual last message text
            (SELECT message_text FROM messages 
             WHERE (sender_id = u.user_id AND receiver_id = :current_user1)
                OR (sender_id = :current_user2 AND receiver_id = u.user_id)
             ORDER BY sent_at DESC LIMIT 1) as last_message,
             
            -- Get unread count
            (SELECT COUNT(*) FROM messages 
             WHERE receiver_id = :current_user3 AND sender_id = u.user_id AND is_read = FALSE) as unread_count
             
        FROM users u
        JOIN (
            SELECT 
                CASE 
                    WHEN sender_id = :current_user4 THEN receiver_id 
                    ELSE sender_id 
                END as contact_id,
                MAX(sent_at) as last_message_time
            FROM messages
            WHERE sender_id = :current_user5 OR receiver_id = :current_user6
            GROUP BY contact_id
        ) last_msg ON u.user_id = last_msg.contact_id
        ORDER BY last_msg.last_message_time DESC
    ";

    $stmt = $conn->prepare($query);

    // Bind all parameters
    $stmt->bindValue(':current_user1', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user2', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user3', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user4', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user5', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':current_user6', $currentUserId, PDO::PARAM_INT);

    $stmt->execute();

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(true, $conversations, 'Conversations retrieved');

} catch (PDOException $e) {
    error_log("Error getting conversations: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to retrieve conversations');
}
?>