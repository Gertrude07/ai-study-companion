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

if ($_SESSION['role'] !== 'student') {
    sendJsonResponse(false, null, 'Only students can access this');
}

$database = new Database();
$conn = $database->getConnection();
$studentId = $_SESSION['user_id'];

try {
    // Get student's enrolled teacher
    $query = "
        SELECT u.user_id, u.full_name, u.email
        FROM teacher_students ts
        JOIN users u ON ts.teacher_id = u.user_id
        WHERE ts.student_id = :student_id
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher) {
        sendJsonResponse(true, $teacher, 'Teacher found');
    } else {
        sendJsonResponse(false, null, 'You are not enrolled in a class yet');
    }

} catch (PDOException $e) {
    error_log("Error getting teacher: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to retrieve teacher information');
}
?>