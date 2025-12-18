<?php
// Study Material Management Class - Handles CRUD operations

require_once __DIR__ . '/../config/database.php';

class StudyMaterial
{
    private $conn;
    private $materialsTable = 'study_materials';
    private $flashcardsTable = 'flashcards';
    private $quizQuestionsTable = 'quiz_questions';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create summary material
    public function createSummary($noteId, $summary)
    {
        $query = "INSERT INTO " . $this->materialsTable . " (note_id, material_type, content) 
                  VALUES (:note_id, 'summary', :content)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindParam(':content', $summary);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'material_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Create summary error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to create summary'];
    }

    // Create flashcard set with individual flashcards
    public function createFlashcardSet($noteId, $flashcards)
    {
        try {
            $this->conn->beginTransaction();

            // Create flashcard set material
            $query = "INSERT INTO " . $this->materialsTable . " (note_id, material_type) 
                      VALUES (:note_id, 'flashcard_set')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->execute();

            $materialId = $this->conn->lastInsertId();

            // Insert individual flashcards
            $insertQuery = "INSERT INTO " . $this->flashcardsTable . " (material_id, question, answer, order_num) 
                            VALUES (:material_id, :question, :answer, :order_num)";

            $insertStmt = $this->conn->prepare($insertQuery);

            foreach ($flashcards as $flashcard) {
                $insertStmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
                $insertStmt->bindParam(':question', $flashcard['question']);
                $insertStmt->bindParam(':answer', $flashcard['answer']);
                $insertStmt->bindParam(':order_num', $flashcard['order_num'], PDO::PARAM_INT);
                $insertStmt->execute();
            }

            $this->conn->commit();

            return [
                'success' => true,
                'material_id' => $materialId
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Create flashcard set error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create flashcard set'];
        }
    }

    // Create quiz set with questions
    public function createQuizSet($noteId, $questions)
    {
        try {
            $this->conn->beginTransaction();

            // Create quiz set material
            $query = "INSERT INTO " . $this->materialsTable . " (note_id, material_type) 
                      VALUES (:note_id, 'quiz_set')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->execute();

            $materialId = $this->conn->lastInsertId();

            // Insert quiz questions
            $insertQuery = "INSERT INTO " . $this->quizQuestionsTable . " 
                            (material_id, question_text, question_type, correct_answer, options, order_num) 
                            VALUES (:material_id, :question_text, :question_type, :correct_answer, :options, :order_num)";

            $insertStmt = $this->conn->prepare($insertQuery);

            foreach ($questions as $question) {
                $options = isset($question['options']) ? json_encode($question['options']) : null;

                $insertStmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
                $insertStmt->bindParam(':question_text', $question['question_text']);
                $insertStmt->bindParam(':question_type', $question['question_type']);
                $insertStmt->bindParam(':correct_answer', $question['correct_answer']);
                $insertStmt->bindParam(':options', $options);
                $insertStmt->bindParam(':order_num', $question['order_num'], PDO::PARAM_INT);
                $insertStmt->execute();
            }

            $this->conn->commit();

            return [
                'success' => true,
                'material_id' => $materialId
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Create quiz set error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create quiz set'];
        }
    }

    // Get material by note ID and type
    public function getByNoteAndType($noteId, $type)
    {
        $query = "SELECT * FROM " . $this->materialsTable . " 
                  WHERE note_id = :note_id AND material_type = :type";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->bindParam(':type', $type);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
        } catch (PDOException $e) {
            error_log("Get material error: " . $e->getMessage());
        }

        return false;
    }

    // Get all materials for a note
    public function getAllByNote($noteId)
    {
        $query = "SELECT * FROM " . $this->materialsTable . " WHERE note_id = :note_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all materials error: " . $e->getMessage());
            return [];
        }
    }

    // Get flashcards for a material
    public function getFlashcards($materialId)
    {
        $query = "SELECT * FROM " . $this->flashcardsTable . " 
                  WHERE material_id = :material_id 
                  ORDER BY order_num";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get flashcards error: " . $e->getMessage());
            return [];
        }
    }

    // Get quiz questions for a material
    public function getQuizQuestions($materialId)
    {
        $query = "SELECT * FROM " . $this->quizQuestionsTable . " 
                  WHERE material_id = :material_id 
                  ORDER BY order_num";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->execute();

            $questions = $stmt->fetchAll();

            // Parse JSON options
            foreach ($questions as &$question) {
                if ($question['options']) {
                    $question['options'] = json_decode($question['options'], true);
                }
            }

            return $questions;
        } catch (PDOException $e) {
            error_log("Get quiz questions error: " . $e->getMessage());
            return [];
        }
    }

    // Update last accessed timestamp
    public function updateLastAccessed($materialId)
    {
        $query = "UPDATE " . $this->materialsTable . " 
                  SET last_accessed = CURRENT_TIMESTAMP 
                  WHERE material_id = :material_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update last accessed error: " . $e->getMessage());
            return false;
        }
    }

    // Check if materials exist for a note
    public function materialsExist($noteId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->materialsTable . " WHERE note_id = :note_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Check materials exist error: " . $e->getMessage());
            return false;
        }
    }

    // Delete all materials for a note
    public function deleteByNote($noteId)
    {
        $query = "DELETE FROM " . $this->materialsTable . " WHERE note_id = :note_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete materials error: " . $e->getMessage());
            return false;
        }
    }
}
?>