<?php
// Remove Student API Endpoint - Teacher removes a student from their class

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
header('Content-Type: application/json');

// Check if user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    sendJsonResponse(false, null, 'Only teachers can remove students');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

// Get and sanitize input
$studentId = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

// Validate input
if (empty($studentId)) {
    sendJsonResponse(false, null, 'Student ID is required');
}

$database = new Database();
$conn = $database->getConnection();
$teacherId = $_SESSION['user_id'];

try {
    // Verify enrollment exists
    $checkQuery = "SELECT id FROM teacher_students WHERE teacher_id = :teacher_id AND student_id = :student_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $checkStmt->execute();

    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(false, null, 'This student is not enrolled in your class');
    }

    // Delete enrollment
    $deleteQuery = "DELETE FROM teacher_students WHERE teacher_id = :teacher_id AND student_id = :student_id";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
    $deleteStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $deleteStmt->execute();

    sendJsonResponse(true, null, 'Student removed successfully');

} catch (PDOException $e) {
    error_log("Error removing student: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to remove student. Please try again.');
}
?>