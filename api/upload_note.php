<?php
// File Upload API Endpoint - Handles note uploads and text extraction

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Note.php';
require_once __DIR__ . '/../classes/FileParser.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, null, 'Invalid request method');
}

$userId = $_SESSION['user_id'];
$title = sanitizeInput($_POST['title'] ?? '');
$file = $_FILES['note_file'] ?? null;

// Validate title
if (empty($title)) {
    sendJsonResponse(false, null, 'Title is required');
}

// Validate file upload
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, null, 'File upload failed');
}

// Validate file type
$allowedMimeTypes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip', // DOCX often detected as zip
    'application/octet-stream', // Generic binary
    'text/plain'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    sendJsonResponse(false, null, 'Invalid file type. Supported formats: PDF, DOCX, TXT');
}

// Validate file size (10MB)
$maxSize = getMaxUploadSize();
if ($file['size'] > $maxSize) {
    sendJsonResponse(false, null, 'File too large. Maximum size is ' . formatFileSize($maxSize));
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('note_') . '_' . time() . '.' . $extension;
$uploadDir = __DIR__ . '/../uploads/';
$filepath = $uploadDir . $filename;

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    sendJsonResponse(false, null, 'Failed to save file');
}

// Extract text based on file type
$extractedText = '';

// Normalize MIME type for generic types based on extension
if ($mimeType === 'application/zip' || $mimeType === 'application/octet-stream') {
    $ext = strtolower($extension);
    if ($ext === 'docx') {
        $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    } elseif ($ext === 'pdf') {
        $mimeType = 'application/pdf';
    }
}

try {
    // Use the FileParser class for all file types
    $extractedText = FileParser::extractText($filepath, $mimeType);

    // Clean up extracted text
    $extractedText = trim($extractedText);

    if (empty($extractedText)) {
        throw new Exception('No text could be extracted from the file');
    }

} catch (Exception $e) {
    error_log("Text extraction error: " . $e->getMessage());
    // Don't delete file, keep it for manual review
    sendJsonResponse(false, null, $e->getMessage());
}

// Save note to database
$noteObj = new Note();
$result = $noteObj->create($userId, $title, $file['name'], $filepath, $extractedText);

if ($result['success']) {
    sendJsonResponse(true, [
        'note_id' => $result['note_id'],
        'title' => $title,
        'text_length' => strlen($extractedText),
        'word_count' => str_word_count($extractedText)
    ], 'Note uploaded and processed successfully');
} else {
    // Delete uploaded file if database save fails
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    sendJsonResponse(false, null, $result['message'] ?? 'Failed to save note to database');
}
?>