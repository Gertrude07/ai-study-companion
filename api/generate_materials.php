<?php
// Generate Study Materials API Endpoint - Uses AI to generate content

// Start output buffering to prevent any accidental output
ob_start();

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/AIProcessor.php';
require_once __DIR__ . '/../classes/StudyMaterial.php';

// Increase execution time limit for AI generation
set_time_limit(180); // 3 minutes
ini_set('max_execution_time', '180');

requireLogin();

// Clean any output buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$userId = $_SESSION['user_id'];
$noteId = intval($_POST['note_id'] ?? 0);

if ($noteId <= 0) {
    sendJsonResponse(false, null, 'Invalid note ID');
}

// Get note and verify ownership
$noteObj = new Note();
$note = $noteObj->getById($noteId, $userId);

if (!$note) {
    sendJsonResponse(false, null, 'Note not found or access denied');
}

// Check if text was extracted
if (empty($note['extracted_text'])) {
    error_log("No extracted text for note ID: $noteId. File: {$note['file_path']}");
    sendJsonResponse(false, null, 'No text content available. The file may not have been processed correctly.');
}

error_log("Generating materials for note ID: $noteId with text length: " . strlen($note['extracted_text']));

// Check if materials already exist and allow regeneration
$studyMaterialObj = new StudyMaterial();
$forceRegenerate = isset($_POST['regenerate']) && $_POST['regenerate'] === 'true';

if ($studyMaterialObj->materialsExist($noteId)) {
    if (!$forceRegenerate) {
        sendJsonResponse(false, null, 'Materials already generated for this note');
    } else {
        // Delete existing materials to regenerate
        error_log("Regenerating materials for note ID: $noteId");
        $studyMaterialObj->deleteByNote($noteId);
    }
}

// Wrap everything in try-catch to ensure JSON response
try {
    // Initialize AI processor
    $aiProcessor = new AIProcessor();
    $noteText = $note['extracted_text'];

    $results = [
        'summary' => null,
        'flashcards' => null,
        'quiz' => null
    ];

    $errors = []; // Track specific errors

    // Generate Summary
    $summaryResult = $aiProcessor->generateSummary($noteText);
    if ($summaryResult['success']) {
        $materialResult = $studyMaterialObj->createSummary($noteId, $summaryResult['summary']);
        if ($materialResult['success']) {
            $results['summary'] = [
                'material_id' => $materialResult['material_id'],
                'content' => $summaryResult['summary']
            ];
        } else {
            $errors[] = "Summary DB save failed: " . ($materialResult['message'] ?? 'Unknown error');
        }
    } else {
        $errors[] = "Summary generation failed: " . ($summaryResult['message'] ?? 'AI did not respond');
        error_log("Summary generation error: " . ($summaryResult['message'] ?? 'No message'));
    }

    // Add delay to avoid rate limiting
    sleep(2);

    // Generate 1 flashcard set (15 cards)
    $flashcardsResult = $aiProcessor->generateFlashcards($noteText, 15);
    if ($flashcardsResult['success']) {
        $materialResult = $studyMaterialObj->createFlashcardSet($noteId, $flashcardsResult['flashcards']);
        if ($materialResult['success']) {
            $results['flashcards'] = [
                'material_id' => $materialResult['material_id'],
                'count' => count($flashcardsResult['flashcards'])
            ];
        } else {
            $errors[] = "Flashcards DB save failed: " . ($materialResult['message'] ?? 'Unknown error');
        }
    } else {
        $errors[] = "Flashcards generation failed: " . ($flashcardsResult['message'] ?? 'AI did not respond');
        error_log("Flashcards generation error: " . ($flashcardsResult['message'] ?? 'No message'));
    }

    // Add delay to avoid rate limiting
    sleep(2);

    // Generate Quiz
    $quizResult = $aiProcessor->generateQuiz($noteText);
    if ($quizResult['success']) {
        $materialResult = $studyMaterialObj->createQuizSet($noteId, $quizResult['questions']);
        if ($materialResult['success']) {
            $results['quiz'] = [
                'material_id' => $materialResult['material_id'],
                'count' => count($quizResult['questions'])
            ];
        } else {
            $errors[] = "Quiz DB save failed: " . ($materialResult['message'] ?? 'Unknown error');
        }
    } else {
        $errors[] = "Quiz generation failed: " . ($quizResult['message'] ?? 'AI did not respond');
        error_log("Quiz generation error: " . ($quizResult['message'] ?? 'No message'));
    }

    // Check if at least one material was generated
    $generatedCount = count(array_filter($results));

    if ($generatedCount > 0) {
        $message = 'Study materials generated successfully (' . $generatedCount . ' of 3)';
        if (!empty($errors)) {
            $message .= '. Some items failed: ' . implode('; ', $errors);
        }
        sendJsonResponse(true, $results, $message);
    } else {
        // All failed - provide detailed error
        $detailedError = 'Failed to generate any study materials. Errors: ' . implode(' | ', $errors);
        error_log("All materials failed: " . $detailedError);
        sendJsonResponse(false, ['errors' => $errors], $detailedError);
    }
} catch (Exception $e) {
    error_log("Fatal generation error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error generating materials: ' . $e->getMessage());
}
?>