<?php
// User Management Class - Handles registration, authentication, profile

require_once __DIR__ . '/../config/database.php';

class User
{
    private $conn;
    private $table = 'users';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Register a new user
    public function register($fullName, $email, $password, $role = 'student')
    {
        // Validate input
        if (empty($fullName) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Validate role
        if (!in_array($role, ['student', 'teacher'])) {
            $role = 'student';
        }

        // Check if email already exists
        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Generate class code for teachers
        $classCode = null;
        if ($role === 'teacher') {
            $classCode = $this->generateUniqueClassCode();
        }

        // Insert user
        $query = "INSERT INTO " . $this->table . " (full_name, email, password_hash, role, class_code) VALUES (:full_name, :email, :password_hash, :role, :class_code)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $passwordHash);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':class_code', $classCode);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    // Generate a unique class code for teachers
    private function generateUniqueClassCode()
    {
        do {
            // Generate format: ABC-123
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3)) . '-' .
                strtoupper(substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3));

            // Check if code already exists
            $query = "SELECT user_id FROM " . $this->table . " WHERE class_code = :code";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->execute();
        } while ($stmt->rowCount() > 0);

        return $code;
    }

    // Authenticate user login
    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $query = "SELECT user_id, full_name, email, password_hash, role FROM " . $this->table . " WHERE email = :email";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();

                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Update last login
                    $this->updateLastLogin($user['user_id']);

                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'user_id' => $user['user_id'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'role' => $user['role'] ?? 'student'
                        ]
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    // Get user by ID
    public function getUserById($userId)
    {
        $query = "SELECT user_id, full_name, email, created_at, last_login FROM " . $this->table . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch();
            }
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
        }

        return false;
    }

    // Update user's last login timestamp
    public function updateLastLogin($userId)
    {
        $query = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }

    // Check if email already exists
    private function emailExists($email)
    {
        $query = "SELECT user_id FROM " . $this->table . " WHERE email = :email";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }

    // Update user profile
    public function updateProfile($userId, $fullName)
    {
        $query = "UPDATE " . $this->table . " SET full_name = :full_name WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $fullName);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }

    // Change user password
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        // Get current password hash
        $query = "SELECT password_hash FROM " . $this->table . " WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();

                // Verify old password
                if (password_verify($oldPassword, $user['password_hash'])) {
                    // Update password
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateQuery = "UPDATE " . $this->table . " SET password_hash = :password_hash WHERE user_id = :user_id";

                    $updateStmt = $this->conn->prepare($updateQuery);
                    $updateStmt->bindParam(':password_hash', $newPasswordHash);
                    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

                    if ($updateStmt->execute()) {
                        return ['success' => true, 'message' => 'Password updated successfully'];
                    }
                }
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update password'];
    }

    // Delete user account and all related data
    public function deleteAccount($userId)
    {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // Get all user's notes to delete files
            $query = "SELECT file_path FROM notes WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Delete quiz answers
            $query = "DELETE qa FROM quiz_answers qa
                     INNER JOIN quiz_attempts qat ON qa.attempt_id = qat.attempt_id
                     WHERE qat.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete quiz attempts
            $query = "DELETE FROM quiz_attempts WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete quiz questions (through study_materials)
            $query = "DELETE qq FROM quiz_questions qq
                     INNER JOIN study_materials sm ON qq.material_id = sm.material_id
                     INNER JOIN notes n ON sm.note_id = n.note_id
                     WHERE n.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete flashcards
            $query = "DELETE fc FROM flashcards fc
                     INNER JOIN study_materials sm ON fc.material_id = sm.material_id
                     INNER JOIN notes n ON sm.note_id = n.note_id
                     WHERE n.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete study materials
            $query = "DELETE sm FROM study_materials sm
                     INNER JOIN notes n ON sm.note_id = n.note_id
                     WHERE n.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete notes
            $query = "DELETE FROM notes WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete user
            $query = "DELETE FROM " . $this->table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();

            // Delete physical files
            foreach ($notes as $note) {
                $filePath = __DIR__ . '/../' . $note['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return ['success' => true, 'message' => 'Account deleted successfully'];

        } catch (PDOException $e) {
            // Rollback on error
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Delete account error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Store remember token
    public function storeRememberToken($userId, $token)
    {
        $hash = hash('sha256', $token);
        // Set expiry to 30 days
        $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

        $query = "UPDATE " . $this->table . " SET remember_token = :token, remember_expires = :expiry WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $hash);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Store remember token error: " . $e->getMessage());
            return false;
        }
    }

    // Verify remember token
    public function verifyRememberToken($token)
    {
        $hash = hash('sha256', $token);

        $query = "SELECT user_id, full_name, email, role, remember_expires FROM " . $this->table . " WHERE remember_token = :token";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $hash);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();

                // Check if expired
                if (strtotime($user['remember_expires']) > time()) {
                    return $user;
                }
            }
        } catch (PDOException $e) {
            error_log("Verify remember token error: " . $e->getMessage());
        }

        return false;
    }

    // Remove remember token
    public function removeRememberToken($userId)
    {
        $query = "UPDATE " . $this->table . " SET remember_token = NULL, remember_expires = NULL WHERE user_id = :user_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Remove remember token error: " . $e->getMessage());
            return false;
        }
    }


    // Generate random remember token
    public function generateRememberToken()
    {
        return bin2hex(random_bytes(32));
    }
}
?>