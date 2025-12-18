-- Update CASCADE constraints to ensure proper deletion
-- Run this script if you're having issues with material deletion

USE study_companion;

-- First, drop existing foreign keys
ALTER TABLE study_materials DROP FOREIGN KEY IF EXISTS study_materials_ibfk_1;
ALTER TABLE flashcards DROP FOREIGN KEY IF EXISTS flashcards_ibfk_1;
ALTER TABLE quiz_questions DROP FOREIGN KEY IF EXISTS quiz_questions_ibfk_1;
ALTER TABLE quiz_attempts DROP FOREIGN KEY IF EXISTS quiz_attempts_ibfk_1;
ALTER TABLE quiz_attempts DROP FOREIGN KEY IF EXISTS quiz_attempts_ibfk_2;
ALTER TABLE study_sessions DROP FOREIGN KEY IF EXISTS study_sessions_ibfk_1;
ALTER TABLE study_sessions DROP FOREIGN KEY IF EXISTS study_sessions_ibfk_2;
ALTER TABLE quiz_answers DROP FOREIGN KEY IF EXISTS quiz_answers_ibfk_1;
ALTER TABLE quiz_answers DROP FOREIGN KEY IF EXISTS quiz_answers_ibfk_2;

-- Re-add foreign keys with CASCADE delete
ALTER TABLE study_materials
    ADD CONSTRAINT study_materials_ibfk_1 
    FOREIGN KEY (note_id) REFERENCES notes(note_id) 
    ON DELETE CASCADE;

ALTER TABLE flashcards
    ADD CONSTRAINT flashcards_ibfk_1 
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) 
    ON DELETE CASCADE;

ALTER TABLE quiz_questions
    ADD CONSTRAINT quiz_questions_ibfk_1 
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) 
    ON DELETE CASCADE;

ALTER TABLE quiz_attempts
    ADD CONSTRAINT quiz_attempts_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
    ON DELETE CASCADE;

ALTER TABLE quiz_attempts
    ADD CONSTRAINT quiz_attempts_ibfk_2 
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) 
    ON DELETE CASCADE;

ALTER TABLE study_sessions
    ADD CONSTRAINT study_sessions_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES users(user_id) 
    ON DELETE CASCADE;

ALTER TABLE study_sessions
    ADD CONSTRAINT study_sessions_ibfk_2 
    FOREIGN KEY (material_id) REFERENCES study_materials(material_id) 
    ON DELETE CASCADE;

ALTER TABLE quiz_answers
    ADD CONSTRAINT quiz_answers_ibfk_1 
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(attempt_id) 
    ON DELETE CASCADE;

ALTER TABLE quiz_answers
    ADD CONSTRAINT quiz_answers_ibfk_2 
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) 
    ON DELETE CASCADE;

-- Verify constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE 
    TABLE_SCHEMA = 'study_companion'
    AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY 
    TABLE_NAME, CONSTRAINT_NAME;
