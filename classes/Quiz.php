<?php
// Quiz Management Class - Handles attempts and scoring

require_once __DIR__ . '/../config/database.php';

class Quiz
{
    private $conn;
    private $attemptsTable = 'quiz_attempts';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Save quiz attempt
    public function saveAttempt($userId, $materialId, $score, $totalQuestions, $correctAnswers, $timeTaken = null)
    {
        $query = "INSERT INTO " . $this->attemptsTable . " 
                  (user_id, material_id, score, total_questions, correct_answers, time_taken) 
                  VALUES (:user_id, :material_id, :score, :total_questions, :correct_answers, :time_taken)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->bindParam(':score', $score);
            $stmt->bindParam(':total_questions', $totalQuestions, PDO::PARAM_INT);
            $stmt->bindParam(':correct_answers', $correctAnswers, PDO::PARAM_INT);
            $stmt->bindParam(':time_taken', $timeTaken, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'attempt_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Save quiz attempt error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to save quiz attempt'];
    }

    // Get attempt by ID
    public function getAttemptById($attemptId)
    {
        $query = "SELECT qa.*, sm.note_id, n.title as note_title 
                  FROM " . $this->attemptsTable . " qa
                  JOIN study_materials sm ON qa.material_id = sm.material_id
                  JOIN notes n ON sm.note_id = n.note_id
                  WHERE qa.attempt_id = :attempt_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
        } catch (PDOException $e) {
            error_log("Get attempt error: " . $e->getMessage());
        }

        return false;
    }

    // Get all attempts for a user
    public function getAttemptsByUser($userId, $limit = 50)
    {
        $query = "SELECT qa.*, sm.note_id, n.title as note_title 
                  FROM " . $this->attemptsTable . " qa
                  JOIN study_materials sm ON qa.material_id = sm.material_id
                  JOIN notes n ON sm.note_id = n.note_id
                  WHERE qa.user_id = :user_id 
                  ORDER BY qa.completed_at DESC 
                  LIMIT :limit";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get attempts by user error: " . $e->getMessage());
            return [];
        }
    }

    // Get attempts for a specific material
    public function getAttemptsByMaterial($materialId, $userId)
    {
        $query = "SELECT * FROM " . $this->attemptsTable . " 
                  WHERE material_id = :material_id AND user_id = :user_id 
                  ORDER BY completed_at DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get attempts by material error: " . $e->getMessage());
            return [];
        }
    }

    // Get recent quiz attempts
    public function getRecentAttempts($userId, $limit = 10)
    {
        return $this->getAttemptsByUser($userId, $limit);
    }

    // Get average score for user
    public function getAverageScore($userId)
    {
        $query = "SELECT AVG(score) as avg_score FROM " . $this->attemptsTable . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['avg_score'] ? round($result['avg_score'], 2) : 0;
        } catch (PDOException $e) {
            error_log("Get average score error: " . $e->getMessage());
            return 0;
        }
    }

    // Get total quiz count for user
    public function getTotalQuizCount($userId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->attemptsTable . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get total quiz count error: " . $e->getMessage());
            return 0;
        }
    }

    // Get best score for a material
    public function getBestScore($materialId, $userId)
    {
        $query = "SELECT MAX(score) as best_score FROM " . $this->attemptsTable . " 
                  WHERE material_id = :material_id AND user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['best_score'] ? round($result['best_score'], 2) : 0;
        } catch (PDOException $e) {
            error_log("Get best score error: " . $e->getMessage());
            return 0;
        }
    }

    // Calculate score from answers
    public function calculateScore($questions, $userAnswers)
    {
        $totalQuestions = count($questions);
        $correctAnswers = 0;
        $results = [];

        foreach ($questions as $index => $question) {
            $questionId = $question['question_id'];
            $userAnswer = $userAnswers[$questionId] ?? '';
            $correctAnswer = $question['correct_answer'];

            $isCorrect = $this->compareAnswers($userAnswer, $correctAnswer, $question['question_type']);

            if ($isCorrect) {
                $correctAnswers++;
            }

            $results[] = [
                'question_id' => $questionId,
                'question_text' => $question['question_text'],
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect
            ];
        }

        $score = ($totalQuestions > 0) ? ($correctAnswers / $totalQuestions) * 100 : 0;

        return [
            'score' => round($score, 2),
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'results' => $results
        ];
    }

    // Get attempt results with detailed question breakdown
    public function getAttemptResults($attemptId, $userId)
    {
        try {
            // Get attempt details
            $query = "SELECT qa.*, sm.note_id, n.title as note_title 
                      FROM " . $this->attemptsTable . " qa
                      JOIN study_materials sm ON qa.material_id = sm.material_id
                      JOIN notes n ON sm.note_id = n.note_id
                      WHERE qa.attempt_id = :attempt_id AND qa.user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $attempt = $stmt->fetch();

            if (!$attempt) {
                return false;
            }

            // Get quiz questions with user answers for this attempt
            $query = "SELECT qq.question_id, qq.question_text, qq.question_type, 
                             qq.correct_answer, qq.options, 
                             qa.user_answer, qa.is_correct
                      FROM quiz_questions qq
                      LEFT JOIN quiz_answers qa ON qq.question_id = qa.question_id 
                                                AND qa.attempt_id = :attempt_id
                      WHERE qq.material_id = :material_id 
                      ORDER BY qq.order_num";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
            $stmt->bindParam(':material_id', $attempt['material_id'], PDO::PARAM_INT);
            $stmt->execute();

            $questions = $stmt->fetchAll();

            // Parse JSON options
            foreach ($questions as &$question) {
                if ($question['options']) {
                    $question['options'] = json_decode($question['options'], true);
                }
            }

            // Add questions to attempt data
            $attempt['questions'] = $questions;

            return $attempt;
        } catch (PDOException $e) {
            error_log("Get attempt results error: " . $e->getMessage());
            return false;
        }
    }

    // Submit quiz and calculate score
    public function submitQuiz($userId, $noteId, $answers, $duration = null)
    {
        try {
            // Get quiz material_id from note_id
            $query = "SELECT material_id FROM study_materials 
                      WHERE note_id = :note_id AND material_type = 'quiz_set' 
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':note_id', $noteId, PDO::PARAM_INT);
            $stmt->execute();

            $material = $stmt->fetch();
            if (!$material) {
                return [
                    'success' => false,
                    'message' => 'No quiz found for this note'
                ];
            }

            $materialId = $material['material_id'];

            // Get quiz questions
            $query = "SELECT question_id, question_text, question_type, correct_answer, options 
                      FROM quiz_questions 
                      WHERE material_id = :material_id 
                      ORDER BY order_num";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->execute();

            $questions = $stmt->fetchAll();

            if (empty($questions)) {
                return [
                    'success' => false,
                    'message' => 'No quiz questions found'
                ];
            }

            // Calculate score
            $scoreData = $this->calculateScore($questions, $answers);

            // Save attempt
            $saveResult = $this->saveAttempt(
                $userId,
                $materialId,
                $scoreData['score'],
                $scoreData['total_questions'],
                $scoreData['correct_answers'],
                $duration
            );

            if ($saveResult['success']) {
                $attemptId = $saveResult['attempt_id'];

                // Save individual answers for review
                $this->saveQuizAnswers($attemptId, $scoreData['results']);

                return [
                    'success' => true,
                    'attempt_id' => $attemptId,
                    'score' => $scoreData['score'],
                    'total_questions' => $scoreData['total_questions'],
                    'correct_answers' => $scoreData['correct_answers'],
                    'results' => $scoreData['results']
                ];
            } else {
                return $saveResult;
            }
        } catch (PDOException $e) {
            error_log("Submit quiz error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Save individual quiz answers for review
    private function saveQuizAnswers($attemptId, $results)
    {
        try {
            $query = "INSERT INTO quiz_answers (attempt_id, question_id, user_answer, is_correct) 
                      VALUES (:attempt_id, :question_id, :user_answer, :is_correct)";
            $stmt = $this->conn->prepare($query);

            foreach ($results as $result) {
                $stmt->bindParam(':attempt_id', $attemptId, PDO::PARAM_INT);
                $stmt->bindParam(':question_id', $result['question_id'], PDO::PARAM_INT);
                $stmt->bindParam(':user_answer', $result['user_answer']);
                $stmt->bindParam(':is_correct', $result['is_correct'], PDO::PARAM_BOOL);
                $stmt->execute();
            }

            return true;
        } catch (PDOException $e) {
            error_log("Save quiz answers error: " . $e->getMessage());
            return false;
        }
    }

    // Compare user answer with correct answer
    private function compareAnswers($userAnswer, $correctAnswer, $questionType)
    {
        if ($questionType === 'multiple_choice') {
            return trim($userAnswer) === trim($correctAnswer);
        } else {
            // For short answer, do case-insensitive comparison and check for key terms
            $userAnswer = strtolower(trim($userAnswer));
            $correctAnswer = strtolower(trim($correctAnswer));

            // Exact match
            if ($userAnswer === $correctAnswer) {
                return true;
            }

            // Check if user answer contains the main concept (at least 50% of correct answer words)
            $correctWords = explode(' ', $correctAnswer);
            $matchCount = 0;

            foreach ($correctWords as $word) {
                if (strlen($word) > 3 && strpos($userAnswer, $word) !== false) {
                    $matchCount++;
                }
            }

            $matchPercentage = count($correctWords) > 0 ? ($matchCount / count($correctWords)) : 0;
            return $matchPercentage >= 0.5;
        }
    }
}
?>