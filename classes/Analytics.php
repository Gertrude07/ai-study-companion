<?php
// Analytics Class - Handles progress tracking and stats

require_once __DIR__ . '/../config/database.php';

class Analytics
{
    private $conn;
    private $sessionsTable = 'study_sessions';
    private $attemptsTable = 'quiz_attempts';
    private $notesTable = 'notes';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Save study session
    public function saveSession($userId, $materialId, $sessionType, $duration = 0)
    {
        $query = "INSERT INTO " . $this->sessionsTable . " (user_id, material_id, session_type, duration) 
                  VALUES (:user_id, :material_id, :session_type, :duration)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':material_id', $materialId, PDO::PARAM_INT);
            $stmt->bindParam(':session_type', $sessionType);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Save session error: " . $e->getMessage());
            return false;
        }
    }

    // Get total study sessions count
    public function getTotalSessions($userId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->sessionsTable . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get total sessions error: " . $e->getMessage());
            return 0;
        }
    }

    // Get total study time in seconds
    public function getTotalStudyTime($userId)
    {
        $query = "SELECT SUM(duration) as total_time FROM " . $this->sessionsTable . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['total_time'] ?? 0;
        } catch (PDOException $e) {
            error_log("Get total study time error: " . $e->getMessage());
            return 0;
        }
    }

    // Get study streak (consecutive days)
    public function getStudyStreak($userId)
    {
        $query = "SELECT DISTINCT DATE(session_date) as study_date 
                  FROM " . $this->sessionsTable . " 
                  WHERE user_id = :user_id 
                  ORDER BY study_date DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($dates)) {
                return 0;
            }

            $streak = 0;
            $currentDate = new DateTime();
            $currentDate->setTime(0, 0, 0);

            foreach ($dates as $dateStr) {
                $studyDate = new DateTime($dateStr);
                $studyDate->setTime(0, 0, 0);

                $diff = $currentDate->diff($studyDate)->days;

                if ($diff === $streak) {
                    $streak++;
                } else {
                    break;
                }
            }

            return $streak;
        } catch (Exception $e) {
            error_log("Get study streak error: " . $e->getMessage());
            return 0;
        }
    }

    // Get average quiz score
    public function getAverageQuizScore($userId)
    {
        $query = "SELECT AVG(score) as avg_score FROM " . $this->attemptsTable . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['avg_score'] ? round($result['avg_score'], 2) : 0;
        } catch (PDOException $e) {
            error_log("Get average quiz score error: " . $e->getMessage());
            return 0;
        }
    }

    // Get recent quiz scores for chart
    public function getRecentQuizScores($userId, $limit = 10)
    {
        $query = "SELECT score, DATE(completed_at) as date, n.title 
                  FROM " . $this->attemptsTable . " qa
                  JOIN study_materials sm ON qa.material_id = sm.material_id
                  JOIN " . $this->notesTable . " n ON sm.note_id = n.note_id
                  WHERE qa.user_id = :user_id 
                  ORDER BY qa.completed_at DESC 
                  LIMIT :limit";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return array_reverse($stmt->fetchAll());
        } catch (PDOException $e) {
            error_log("Get recent quiz scores error: " . $e->getMessage());
            return [];
        }
    }

    // Get weak topics (score < 70%)
    public function getWeakTopics($userId)
    {
        $query = "SELECT n.note_id, n.title, AVG(qa.score) as avg_score, COUNT(qa.attempt_id) as attempt_count
                  FROM " . $this->attemptsTable . " qa
                  JOIN study_materials sm ON qa.material_id = sm.material_id
                  JOIN " . $this->notesTable . " n ON sm.note_id = n.note_id
                  WHERE qa.user_id = :user_id 
                  GROUP BY n.note_id, n.title
                  HAVING avg_score < 70
                  ORDER BY avg_score ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get weak topics error: " . $e->getMessage());
            return [];
        }
    }

    // Get most studied materials
    public function getMostStudiedMaterials($userId, $limit = 5)
    {
        $query = "SELECT n.note_id, n.title, COUNT(ss.session_id) as session_count, SUM(ss.duration) as total_time
                  FROM " . $this->sessionsTable . " ss
                  JOIN study_materials sm ON ss.material_id = sm.material_id
                  JOIN " . $this->notesTable . " n ON sm.note_id = n.note_id
                  WHERE ss.user_id = :user_id 
                  GROUP BY n.note_id, n.title
                  ORDER BY session_count DESC
                  LIMIT :limit";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get most studied materials error: " . $e->getMessage());
            return [];
        }
    }

    // Get study sessions by date for chart
    public function getSessionsByDate($userId, $days = 30)
    {
        $query = "SELECT DATE(session_date) as date, COUNT(*) as session_count, SUM(duration) as total_duration
                  FROM " . $this->sessionsTable . " 
                  WHERE user_id = :user_id AND session_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  GROUP BY DATE(session_date)
                  ORDER BY date ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get sessions by date error: " . $e->getMessage());
            return [];
        }
    }

    // Get comprehensive dashboard stats
    public function getDashboardStats($userId)
    {
        return [
            'total_sessions' => $this->getTotalSessions($userId),
            'total_study_time' => $this->getTotalStudyTime($userId),
            'study_streak' => $this->getStudyStreak($userId),
            'average_score' => $this->getAverageQuizScore($userId),
            'recent_scores' => $this->getRecentQuizScores($userId, 10),
            'weak_topics' => $this->getWeakTopics($userId),
            'most_studied' => $this->getMostStudiedMaterials($userId, 5),
            'sessions_by_date' => $this->getSessionsByDate($userId, 30)
        ];
    }

    // Get user statistics for progress page
    public function getUserStats($userId)
    {
        return [
            'total_notes' => $this->getTotalNotes($userId),
            'total_quizzes' => $this->getTotalQuizzes($userId),
            'total_flashcards' => $this->getTotalFlashcards($userId),
            'average_score' => $this->getAverageQuizScore($userId),
            'study_time' => $this->getTotalStudyTime($userId),
            'sessions' => $this->getTotalSessions($userId)
        ];
    }

    // Get total notes count
    private function getTotalNotes($userId)
    {
        $query = "SELECT COUNT(*) as count FROM notes WHERE user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get total quizzes count
    private function getTotalQuizzes($userId)
    {
        $query = "SELECT COUNT(DISTINCT qa.attempt_id) as count 
                  FROM quiz_attempts qa 
                  JOIN study_materials sm ON qa.material_id = sm.material_id 
                  JOIN notes n ON sm.note_id = n.note_id 
                  WHERE n.user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get total flashcards count
    private function getTotalFlashcards($userId)
    {
        $query = "SELECT COUNT(DISTINCT f.flashcard_id) as count 
                  FROM flashcards f 
                  JOIN study_materials sm ON f.material_id = sm.material_id 
                  JOIN notes n ON sm.note_id = n.note_id 
                  WHERE n.user_id = :user_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get recent activity
    public function getRecentActivity($userId, $limit = 10)
    {
        $query = "SELECT 'quiz' as type, qa.score, qa.attempt_date as date, n.title 
                  FROM quiz_attempts qa 
                  JOIN study_materials sm ON qa.material_id = sm.material_id 
                  JOIN notes n ON sm.note_id = n.note_id 
                  WHERE n.user_id = :user_id 
                  UNION ALL 
                  SELECT ss.session_type as type, NULL as score, ss.session_date as date, n.title 
                  FROM study_sessions ss 
                  JOIN study_materials sm ON ss.material_id = sm.material_id 
                  JOIN notes n ON sm.note_id = n.note_id 
                  WHERE n.user_id = :user_id 
                  ORDER BY date DESC 
                  LIMIT :limit";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get performance over time
    public function getPerformanceOverTime($userId, $days = 30)
    {
        $query = "SELECT DATE(attempt_date) as date, AVG(score) as score 
                  FROM quiz_attempts qa 
                  JOIN study_materials sm ON qa.material_id = sm.material_id 
                  JOIN notes n ON sm.note_id = n.note_id 
                  WHERE n.user_id = :user_id 
                  AND attempt_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY) 
                  GROUP BY DATE(attempt_date) 
                  ORDER BY date ASC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
