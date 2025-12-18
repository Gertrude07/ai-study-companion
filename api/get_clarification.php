<?php
// Get AI Clarification - Provides detailed explanations

// Start output buffering to prevent any accidental output
ob_start();

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

// Clean any output buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/AIProcessor.php';
require_once __DIR__ . '/../classes/Note.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

if (!isset($data['note_id']) || !isset($data['question'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$noteId = (int) $data['note_id'];
$question = trim($data['question']);
$context = $data['context'] ?? ''; // Optional context (e.g., specific flashcard or quiz question)

if (empty($question)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Question cannot be empty']);
    exit;
}

try {
    // Get the original note content for context
    $noteObj = new Note();
    $note = $noteObj->getById($noteId, $_SESSION['user_id']);

    if (!$note) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Note not found or access denied']);
        exit;
    }

    // Get note content - file_path is already relative from root
    $noteContent = file_get_contents(__DIR__ . '/../' . $note['file_path']);

    // Create clarification prompt
    $aiProcessor = new AIProcessor();
    $clarification = $aiProcessor->getClarification($question, $noteContent, $context);

    if ($clarification['success']) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'explanation' => $clarification['explanation']
        ]);
    } else {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $clarification['message'] ?? 'Failed to generate clarification'
        ]);
    }

} catch (Exception $e) {
    error_log("Clarification error: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while generating clarification'
    ]);
}
?>