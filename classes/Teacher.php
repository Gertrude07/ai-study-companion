<?php
// Teacher Class - Handles teacher operations and student monitoring

require_once __DIR__ . '/../config/database.php';

class Teacher
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get all students enrolled in the teacher's class
    public function getAllStudents($teacherId)
    {
        $query = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                u.created_at as joined_date,
                ts.enrolled_date,
                COUNT(DISTINCT n.note_id) as note_count,
                COUNT(DISTINCT qa.attempt_id) as quiz_count,
                COALESCE(ROUND(AVG(qa.score), 2), 0) as avg_score,
                MAX(qa.completed_at) as last_activity
            FROM teacher_students ts
            JOIN users u ON ts.student_id = u.user_id
            LEFT JOIN notes n ON u.user_id = n.user_id
            LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
            WHERE ts.teacher_id = :teacher_id AND u.role = 'student'
            GROUP BY u.user_id
            ORDER BY u.full_name ASC
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting enrolled students: " . $e->getMessage());
            return [];
        }
    }

    // Get detailed information about a specific student
    public function getStudentDetails($studentId)
    {
        $studentId = intval($studentId);

        // Get student basic info
        $query = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                u.created_at as joined_date
            FROM users u
            WHERE u.user_id = :student_id AND u.role = 'student'
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                return null;
            }

            // Get student's notes
            $student['notes'] = $this->getStudentNotes($studentId);

            // Get student's quiz attempts
            $student['quiz_attempts'] = $this->getStudentQuizAttempts($studentId);

            // Calculate statistics
            $student['stats'] = $this->getStudentStats($studentId);

            return $student;
        } catch (PDOException $e) {
            error_log("Error getting student details: " . $e->getMessage());
            return null;
        }
    }

    // Get student's uploaded notes
    private function getStudentNotes($studentId)
    {
        $query = "
            SELECT 
                note_id,
                title,
                original_filename,
                upload_date
            FROM notes
            WHERE user_id = :student_id
            ORDER BY upload_date DESC
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student notes: " . $e->getMessage());
            return [];
        }
    }

    // Get student's quiz attempts
    private function getStudentQuizAttempts($studentId)
    {
        $query = "
            SELECT 
                qa.attempt_id,
                qa.score,
                qa.total_questions,
                qa.completed_at,
                n.title as note_title
            FROM quiz_attempts qa
            JOIN quiz_sets qs ON qa.quiz_id = qs.quiz_id
            JOIN study_materials sm ON qs.material_id = sm.material_id
            JOIN notes n ON sm.note_id = n.note_id
            WHERE qa.user_id = :student_id
            ORDER BY qa.completed_at DESC
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student quiz attempts: " . $e->getMessage());
            return [];
        }
    }

    // Get student statistics
    private function getStudentStats($studentId)
    {
        $query = "
            SELECT 
                COUNT(DISTINCT n.note_id) as total_notes,
                COUNT(DISTINCT sm.material_id) as total_materials,
                COUNT(DISTINCT qa.attempt_id) as total_quizzes,
                COALESCE(ROUND(AVG(qa.score), 2), 0) as avg_score,
                MAX(qa.completed_at) as last_activity
            FROM users u
            LEFT JOIN notes n ON u.user_id = n.user_id
            LEFT JOIN study_materials sm ON n.note_id = sm.note_id
            LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
            WHERE u.user_id = :student_id
            GROUP BY u.user_id
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting student stats: " . $e->getMessage());
            return [
                'total_notes' => 0,
                'total_materials' => 0,
                'total_quizzes' => 0,
                'avg_score' => 0,
                'last_activity' => null
            ];
        }
    }

    // Get overall system statistics
    public function getSystemStats()
    {
        $query = "
            SELECT 
                COUNT(DISTINCT CASE WHEN role = 'student' THEN user_id END) as total_students,
                COUNT(DISTINCT CASE WHEN role = 'teacher' THEN user_id END) as total_teachers,
                COUNT(DISTINCT n.note_id) as total_notes,
                COUNT(DISTINCT sm.material_id) as total_materials,
                COUNT(DISTINCT qa.attempt_id) as total_quizzes,
                COALESCE(ROUND(AVG(qa.score), 2), 0) as avg_score
            FROM users u
            LEFT JOIN notes n ON u.user_id = n.user_id
            LEFT JOIN study_materials sm ON n.note_id = sm.note_id
            LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
        ";

        try {
            $stmt = $this->conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting system stats: " . $e->getMessage());
            return [
                'total_students' => 0,
                'total_teachers' => 0,
                'total_notes' => 0,
                'total_materials' => 0,
                'total_quizzes' => 0,
                'avg_score' => 0
            ];
        }
    }

    // Search students by name or email
    public function searchStudents($searchTerm)
    {
        $searchTerm = '%' . $searchTerm . '%';

        $query = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                COUNT(DISTINCT n.note_id) as note_count,
                COUNT(DISTINCT qa.attempt_id) as quiz_count,
                COALESCE(ROUND(AVG(qa.score), 2), 0) as avg_score
            FROM users u
            LEFT JOIN notes n ON u.user_id = n.user_id
            LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id
            WHERE u.role = 'student' 
            AND (u.full_name LIKE :search1 OR u.email LIKE :search2)
            GROUP BY u.user_id
            ORDER BY u.full_name ASC
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':search1', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':search2', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching students: " . $e->getMessage());
            return [];
        }
    }

    // Enroll a student in teacher's class using class code
    public function enrollStudent($studentId, $classCode)
    {
        // Find teacher by class code
        $query = "SELECT user_id, full_name FROM users WHERE class_code = :code AND role = 'teacher'";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':code', $classCode, PDO::PARAM_STR);
            $stmt->execute();
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                return ['success' => false, 'message' => 'Invalid class code'];
            }

            // Check if already enrolled
            $checkQuery = "SELECT id FROM teacher_students WHERE teacher_id = :teacher_id AND student_id = :student_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':teacher_id', $teacher['user_id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'You are already enrolled in this class'];
            }

            // Enroll student
            $enrollQuery = "INSERT INTO teacher_students (teacher_id, student_id) VALUES (:teacher_id, :student_id)";
            $enrollStmt = $this->conn->prepare($enrollQuery);
            $enrollStmt->bindParam(':teacher_id', $teacher['user_id'], PDO::PARAM_INT);
            $enrollStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $enrollStmt->execute();

            return [
                'success' => true,
                'message' => 'Successfully joined ' . $teacher['full_name'] . "'s class!",
                'teacher_name' => $teacher['full_name']
            ];
        } catch (PDOException $e) {
            error_log("Error enrolling student: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to join class. Please try again.'];
        }
    }

    // Get teacher's class code
    public function getClassCode($teacherId)
    {
        $query = "SELECT class_code FROM users WHERE user_id = :teacher_id AND role = 'teacher'";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['class_code'] : null;
        } catch (PDOException $e) {
            error_log("Error getting class code: " . $e->getMessage());
            return null;
        }
    }

    // Get enrollment count for a teacher
    public function getEnrollmentCount($teacherId)
    {
        $query = "SELECT COUNT(*) as count FROM teacher_students WHERE teacher_id = :teacher_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? intval($result['count']) : 0;
        } catch (PDOException $e) {
            error_log("Error getting enrollment count: " . $e->getMessage());
            return 0;
        }
    }
}
?>