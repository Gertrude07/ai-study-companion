-- Messaging System Database Schema

-- Direct Messages Table
CREATE TABLE IF NOT EXISTS messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_conversation (sender_id, receiver_id, sent_at),
    INDEX idx_unread (receiver_id, is_read)
);

-- Class Discussions Table
CREATE TABLE IF NOT EXISTS class_discussions (
    discussion_id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_teacher_class (teacher_id, created_at)
);

-- Discussion Replies Table
CREATE TABLE IF NOT EXISTS discussion_replies (
    reply_id INT PRIMARY KEY AUTO_INCREMENT,
    discussion_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES class_discussions(discussion_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_discussion (discussion_id, created_at)
);

-- Verify tables
SHOW TABLES LIKE '%message%';
SHOW TABLES LIKE '%discussion%';
