<?php
// Delete Note and All Related Materials

// Start output buffering to prevent any accidental output
ob_start();

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Note.php';

// Clean any output buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$userId = $_SESSION['user_id'];
$noteId = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;

if ($noteId <= 0) {
    sendJsonResponse(false, null, 'Invalid note ID');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJsonResponse(false, null, 'Invalid security token');
}

try {
    $noteObj = new Note();

    // Verify ownership
    $note = $noteObj->getById($noteId, $userId);
    if (!$note) {
        sendJsonResponse(false, null, 'Note not found or access denied');
    }

    // Delete note and all related materials
    $result = $noteObj->delete($noteId, $userId);

    if ($result['success']) {
        sendJsonResponse(true, null, 'Note and all related materials deleted successfully');
    } else {
        sendJsonResponse(false, null, $result['message'] ?? 'Failed to delete note');
    }

} catch (Exception $e) {
    error_log("Delete note error: " . $e->getMessage());
    sendJsonResponse(false, null, 'Error deleting note: ' . $e->getMessage());
}
?>