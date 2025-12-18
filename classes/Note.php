<?php
// Note Management Class - Handles file uploads and CRUD

require_once __DIR__ . '/../config/database.php';

class Note
{
    private $conn;
    private $table = 'notes';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create a new note record
    public function create($userId, $title, $originalFilename, $filePath, $extractedText = '')
    {
        $query = "INSERT INTO " . $this->table . " (user_id, title, original_filename, file_path, extracted_text, status) 
                  VALUES (:user_id, :title, :original_filename, :file_path, :extracted_text, :status)";

        try {
            $stmt = $this->conn->prepare($query);
            $status = !empty($extractedText) ? 'completed' : 'processing';

            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':original_filename', $originalFilename);
            $stmt->bindParam(':file_path', $filePath);
            $stmt->bindParam(':extracted_text', $extractedText);
            $stmt->bindParam(':status', $status);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'note_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Create note error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to create note'];
    }

    // Get note by ID
    public function getById($noteId, $userId)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE note_id = :note_id AND user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
        } catch (PDOException $e) {
            error_log("Get note error: " . $e->getMessage());
        }

        return false;
    }

    // Get all notes for a user
    public function getAllByUser($userId, $limit = 50, $offset = 0)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY upload_date DESC 
                  LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all notes error: " . $e->getMessage());
            return [];
        }
    }

    // Update note text and status
    public function updateText($noteId, $extractedText, $status = 'completed')
    {
        $query = "UPDATE " . $this->table . " 
                  SET extracted_text = :extracted_text, status = :status 
                  WHERE note_id = :note_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':extracted_text', $extractedText);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update note text error: " . $e->getMessage());
            return false;
        }
    }

    // Update note title
    public function updateTitle($noteId, $userId, $title)
    {
        $query = "UPDATE " . $this->table . " 
                  SET title = :title 
                  WHERE note_id = :note_id AND user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update note title error: " . $e->getMessage());
            return false;
        }
    }

    // Delete note
    public function delete($noteId, $userId)
    {
        // Get file path first to delete file
        $note = $this->getById($noteId, $userId);

        if (!$note) {
            return ['success' => false, 'message' => 'Note not found'];
        }

        try {
            // Start transaction
            $this->conn->beginTransaction();

            // With CASCADE constraints, we only need to delete the note
            // All related materials, flashcards, quiz questions, attempts, answers, and sessions
            // will be automatically deleted by the database CASCADE rules

            // Delete note (CASCADE will handle everything else)
            $query = "DELETE FROM " . $this->table . " WHERE note_id = :note_id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Verify deletion worked
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to delete note - note may not exist or access denied');
            }

            // Commit transaction
            $this->conn->commit();

            // Delete physical file
            $filePath = __DIR__ . '/../' . $note['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return ['success' => true, 'message' => 'Note and all related materials deleted successfully'];

        } catch (PDOException $e) {
            // Rollback on error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete note error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get note count for user
    public function getCountByUser($userId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get note count error: " . $e->getMessage());
            return 0;
        }
    }

    // Get recent notes for user
    public function getRecentByUser($userId, $limit = 5)
    {
        return $this->getAllByUser($userId, $limit, 0);
    }
}
?>