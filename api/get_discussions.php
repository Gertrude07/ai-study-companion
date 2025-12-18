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
$currentRole = $_SESSION['role'];

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
            sendJsonResponse(false, null, 'You must be enrolled in a class');
        }
        $teacherId = $teacherResult['teacher_id'];
    }

    // Get all discussions for class
    $query = "
        SELECT 
            d.discussion_id,
            d.title,
            d.content,
            d.is_pinned,
            d.created_at,
            d.updated_at,
            author.full_name as author_name,
            author.role as author_role,
            (SELECT COUNT(*) FROM discussion_replies WHERE discussion_id = d.discussion_id) as reply_count
        FROM class_discussions d
        JOIN users author ON d.author_id = author.user_id
        WHERE d.teacher_id = :teacher_id
        ORDER BY d.is_pinned DESC, d.updated_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $stmt->execute();

    $discussions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendJsonResponse(true, $discussions, 'Discussions retrieved');

} catch (PDOException $e) {
    error_log("Error getting discussions: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to retrieve discussions');
}
?>