<?php
// Script to add remember_me columns to users table

require_once __DIR__ . '/../config/database.php';

echo "Updating database schema for Remember Me feature...\n";

$database = new Database();
$conn = $database->getConnection();

try {
    // Add remember_token column
    $query = "ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL AFTER role";
    $conn->exec($query);
    echo "✅ Added 'remember_token' column.\n";
} catch (PDOException $e) {
    echo "⚠️  Could not add 'remember_token' (might already exist): " . $e->getMessage() . "\n";
}

try {
    // Add remember_expires column
    $query = "ALTER TABLE users ADD COLUMN remember_expires DATETIME NULL AFTER remember_token";
    $conn->exec($query);
    echo "✅ Added 'remember_expires' column.\n";
} catch (PDOException $e) {
    echo "⚠️  Could not add 'remember_expires' (might already exist): " . $e->getMessage() . "\n";
}

try {
    // Add index for performance
    $query = "ALTER TABLE users ADD INDEX idx_remember_token (remember_token)";
    $conn->exec($query);
    echo "✅ Added index on 'remember_token'.\n";
} catch (PDOException $e) {
    echo "⚠️  Could not add index (might already exist): " . $e->getMessage() . "\n";
}

echo "Database update completed!\n";
?>