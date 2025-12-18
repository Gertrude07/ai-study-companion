<?php
// Leave Class API Endpoint - Student leaves a teacher's class

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
header('Content-Type: application/json');

// Check if user is a student
if ($_SESSION['role'] !== 'student') {
    sendJsonResponse(false, null, 'Only students can leave classes');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

// Get and sanitize input
$teacherId = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;

// Validate input
if (empty($teacherId)) {
    sendJsonResponse(false, null, 'Teacher ID is required');
}

$database = new Database();
$conn = $database->getConnection();
$studentId = $_SESSION['user_id'];

try {
    // Verify enrollment exists
    $checkQuery = "SELECT id FROM teacher_students WHERE student_id = :student_id AND teacher_id = :teacher_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $checkStmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'You are not enrolled in this class');
    }

    // Delete enrollment
    $deleteQuery = "DELETE FROM teacher_students WHERE student_id = :student_id AND teacher_id = :teacher_id";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $deleteStmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $deleteStmt->execute();

    sendJsonResponse(true, null, 'Successfully left the class');

} catch (PDOException $e) {
    error_log("Error leaving class: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to leave class. Please try again.');
}
?>