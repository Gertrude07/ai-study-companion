-- Add class code for teachers and create enrollment table

-- Add class_code column to users table (for teachers)
ALTER TABLE users 
ADD COLUMN class_code VARCHAR(10) UNIQUE AFTER role;

-- Create teacher_students junction table for enrollment
CREATE TABLE IF NOT EXISTS teacher_students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (teacher_id, student_id)
);

-- Generate class codes for existing teachers
UPDATE users 
SET class_code = CONCAT(
    UPPER(SUBSTRING(MD5(RAND()), 1, 3)),
    '-',
    UPPER(SUBSTRING(MD5(RAND()), 1, 3))
)
WHERE role = 'teacher' AND class_code IS NULL;

-- Verify changes
DESCRIBE users;
DESCRIBE teacher_students;
