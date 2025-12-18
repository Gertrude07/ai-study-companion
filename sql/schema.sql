-- AI-Powered Study Companion Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS study_companion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE study_companion;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Uploaded notes table
CREATE TABLE notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    extracted_text LONGTEXT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_notes (user_id, upload_date)
) ENGINE=InnoDB;

-- Study materials (summaries, flashcard sets, quiz sets)
CREATE TABLE study_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    note_id INT NOT NULL,
    material_type ENUM('summary', 'flashcard_set', 'quiz_set') NOT NULL,
    content TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP NULL,
    FOREIGN KEY (note_id) REFERENCES notes(note_id) ON DELETE CASCADE,
    INDEX idx_note_materials (note_id, material_type)
) ENGINE=InnoDB;

-- Individual flashcards
CREATE TABLE flashcards (
    flashcard_id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    order_num INT DEFAULT 0,
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) ON DELETE CASCADE,
    INDEX idx_material_flashcards (material_id, order_num)
) ENGINE=InnoDB;

-- Quiz questions
CREATE TABLE quiz_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'short_answer') NOT NULL,
    correct_answer TEXT NOT NULL,
    options JSON NULL,
    order_num INT DEFAULT 0,
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) ON DELETE CASCADE,
    INDEX idx_material_questions (material_id, order_num)
) ENGINE=InnoDB;

-- Quiz attempts/results
CREATE TABLE quiz_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    time_taken INT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) ON DELETE CASCADE,
    INDEX idx_user_attempts (user_id, completed_at)
) ENGINE=InnoDB;

-- Study sessions (for analytics)
CREATE TABLE study_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    session_type ENUM('flashcard', 'quiz', 'summary') NOT NULL,
    duration INT DEFAULT 0,
    session_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id, session_date)
) ENGINE=InnoDB;
