<?php
// Generate Additional Study Materials (On-Demand)

// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';
require_once '../classes/Note.php';
require_once '../classes/AIProcessor.php';
require_once '../classes/StudyMaterial.php';
require_once '../includes/functions.php';

// Increase execution time for AI generation
set_time_limit(120); // 2 minutes
ini_set('max_execution_time', '120');

session_start();

// Clean any output buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get and validate input
$noteId = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
$materialType = isset($_POST['type']) ? $_POST['type'] : ''; // 'flashcards' or 'quiz'

if (!$noteId || !in_array($materialType, ['flashcards', 'quiz'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    // Clear buffer before processing
    ob_clean();
    // Get note and verify ownership
    $noteObj = new Note();
    $note = $noteObj->getById($noteId, $userId);

    if (!$note) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Note not found']);
        exit;
    }

    $noteText = $note['extracted_text'];

    if (empty($noteText)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'No text content found in note']);
        exit;
    }

    // Generate requested material
    $aiProcessor = new AIProcessor();
    $studyMaterialObj = new StudyMaterial();
    $result = [];

    if ($materialType === 'flashcards') {
        error_log("Generating additional flashcard set for note ID: " . $noteId);
        $flashcardsResult = $aiProcessor->generateFlashcards($noteText, 15);

        if ($flashcardsResult['success']) {
            $materialResult = $studyMaterialObj->createFlashcardSet($noteId, $flashcardsResult['flashcards']);
            if ($materialResult['success']) {
                $result = [
                    'success' => true,
                    'material_id' => $materialResult['material_id'],
                    'count' => count($flashcardsResult['flashcards']),
                    'message' => 'New flashcard set generated successfully'
                ];
            } else {
                throw new Exception('Failed to save flashcard set');
            }
        } else {
            throw new Exception($flashcardsResult['message'] ?? 'Failed to generate flashcards');
        }
    } elseif ($materialType === 'quiz') {
        error_log("Generating additional quiz set for note ID: " . $noteId);
        $quizResult = $aiProcessor->generateQuiz($noteText);

        if ($quizResult['success']) {
            $materialResult = $studyMaterialObj->createQuizSet($noteId, $quizResult['questions']);
            if ($materialResult['success']) {
                $result = [
                    'success' => true,
                    'material_id' => $materialResult['material_id'],
                    'count' => count($quizResult['questions']),
                    'message' => 'New quiz set generated successfully'
                ];
            } else {
                throw new Exception('Failed to save quiz set');
            }
        } else {
            throw new Exception($quizResult['message'] ?? 'Failed to generate quiz');
        }
    }

    // Clear buffer one final time and output JSON
    ob_clean();
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Generate more error: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>