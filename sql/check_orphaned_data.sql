-- Test deletion and check for orphaned records
-- Run this AFTER attempting to delete a material to verify cleanup

USE study_companion;

-- Check for orphaned study_materials (materials without a parent note)
SELECT 'Orphaned study_materials' as issue_type, COUNT(*) as count
FROM study_materials sm
LEFT JOIN notes n ON sm.note_id = n.note_id
WHERE n.note_id IS NULL;

-- Check for orphaned flashcards (flashcards without a parent material)
SELECT 'Orphaned flashcards' as issue_type, COUNT(*) as count
FROM flashcards f
LEFT JOIN study_materials sm ON f.material_id = sm.material_id
WHERE sm.material_id IS NULL;

-- Check for orphaned quiz_questions
SELECT 'Orphaned quiz_questions' as issue_type, COUNT(*) as count
FROM quiz_questions qq
LEFT JOIN study_materials sm ON qq.material_id = sm.material_id
WHERE sm.material_id IS NULL;

-- Check for orphaned quiz_attempts
SELECT 'Orphaned quiz_attempts' as issue_type, COUNT(*) as count
FROM quiz_attempts qa
LEFT JOIN study_materials sm ON qa.material_id = sm.material_id
WHERE sm.material_id IS NULL;

-- Check for orphaned study_sessions
SELECT 'Orphaned study_sessions' as issue_type, COUNT(*) as count
FROM study_sessions ss
LEFT JOIN study_materials sm ON ss.material_id = sm.material_id
WHERE sm.material_id IS NULL;

-- Show all materials and their related data counts
SELECT 
    n.note_id,
    n.title,
    COUNT(DISTINCT sm.material_id) as total_materials,
    COUNT(DISTINCT f.flashcard_id) as total_flashcards,
    COUNT(DISTINCT qq.question_id) as total_questions,
    COUNT(DISTINCT qa.attempt_id) as total_attempts,
    COUNT(DISTINCT ss.session_id) as total_sessions
FROM notes n
LEFT JOIN study_materials sm ON n.note_id = sm.note_id
LEFT JOIN flashcards f ON sm.material_id = f.material_id
LEFT JOIN quiz_questions qq ON sm.material_id = qq.material_id
LEFT JOIN quiz_attempts qa ON sm.material_id = qa.material_id
LEFT JOIN study_sessions ss ON sm.material_id = ss.material_id
GROUP BY n.note_id, n.title
ORDER BY n.note_id;
