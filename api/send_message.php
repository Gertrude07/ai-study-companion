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

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

// Get and validate input
$receiverId = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$currentUserId = $_SESSION['user_id'];
$currentRole = $_SESSION['role'];

if ($receiverId === 0 || empty($message)) {
    sendJsonResponse(false, null, 'Invalid input');
}

if ($receiverId === $currentUserId) {
    sendJsonResponse(false, null, 'Cannot send message to yourself');
}

try {
    // Check if receiver exists and get their role
    $receiverQuery = "SELECT user_id, role FROM users WHERE user_id = :receiver_id";
    $receiverStmt = $conn->prepare($receiverQuery);
    $receiverStmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
    $receiverStmt->execute();
    $receiver = $receiverStmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiver) {
        sendJsonResponse(false, null, 'Receiver not found');
    }

    // Verify enrollment relationship
    if ($currentRole === 'student' && $receiver['role'] === 'teacher') {
        $checkQuery = "SELECT id FROM teacher_students WHERE student_id = :student_id AND teacher_id = :teacher_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':student_id', $currentUserId, PDO::PARAM_INT);
        $checkStmt->bindParam(':teacher_id', $receiverId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'You can only message your teacher');
        }
    } elseif ($currentRole === 'teacher' && $receiver['role'] === 'student') {
        $checkQuery = "SELECT id FROM teacher_students WHERE student_id = :student_id AND teacher_id = :teacher_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':student_id', $receiverId, PDO::PARAM_INT);
        $checkStmt->bindParam(':teacher_id', $currentUserId, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse(false, null, 'You can only message your enrolled students');
        }
    } else {
        sendJsonResponse(false, null, 'Messages only allowed between students and teachers');
    }

    // Insert message
    $insertQuery = "INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (:sender_id, :receiver_id, :message)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(':sender_id', $currentUserId, PDO::PARAM_INT);
    $insertStmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
    $insertStmt->bindParam(':message', $message, PDO::PARAM_STR);
    $insertStmt->execute();

    sendJsonResponse(true, ['message_id' => $conn->lastInsertId()], 'Message sent');

} catch (PDOException $e) {
    error_log("Error sending message: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to send message');
}
?>