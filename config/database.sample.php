<?php
/**
 * Database configuration and connection handler
 */

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Check if running on localhost
        $whitelist = ['127.0.0.1', '::1', 'localhost'];

        if (in_array($_SERVER['HTTP_HOST'] ?? 'localhost', $whitelist)) {
            // Local Development Settings
            $this->host = "localhost";
            $this->db_name = "study_companion";
            $this->username = "root";
            $this->password = "";
        } else {
            // Live Server Settings
            // TODO: Update these with your live server details
            $this->host = "localhost";
            $this->db_name = "YOUR_DB_NAME";
            $this->username = "YOUR_DB_USERNAME";
            $this->password = "YOUR_DB_PASSWORD";
        }
    }

    /**
     * Get database connection
     * @return PDO Database connection object
     */
    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Set MySQL timezone to match PHP timezone
            $this->conn->exec("SET time_zone = '+00:00'"); // UTC, change if needed
        } catch (PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }
}
?>