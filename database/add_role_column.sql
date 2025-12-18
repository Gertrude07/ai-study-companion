-- Add role column to users table
-- Run this SQL in phpMyAdmin or MySQL command line

ALTER TABLE users 
ADD COLUMN role ENUM('student', 'teacher') DEFAULT 'student' NOT NULL 
AFTER password_hash;

-- Verify the change
DESCRIBE users;

-- Optional: Create a test teacher account
-- UPDATE users SET role = 'teacher' WHERE email = 'your-email@example.com';
