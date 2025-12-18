<?php
require_once '../config/database.php';
require_once '../classes/Quiz.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['note_id']) || !isset($input['answers']) || !isset($input['duration'])) {
        throw new Exception('Missing required fields');
    }
    
    $noteId = (int)$input['note_id'];
    $answers = $input['answers'];
    $duration = (int)$input['duration'];
    
    // Initialize database and Quiz class
    $db = new Database();
    $quiz = new Quiz();
    
    // Submit quiz and get result
    $result = $quiz->submitQuiz($userId, $noteId, $answers, $duration);
    
    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Failed to submit quiz');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
