<?php
// Database Connection Class - Handles PDO connection
class Database
{
    private $host = "localhost";
    private $db_name = "webtech_2025A_gertrude_akagbo";
    private $username = "gertrude.akagbo";
    private $password = "Amazing1122@";
    private $conn;

    // Get database connection
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }

        return $this->conn;
    }
}
