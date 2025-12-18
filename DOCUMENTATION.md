# AI Study Companion: Technical Documentation

**Author:** Gertrude Akagbo  
**Course:** WEBTECH 2025A  
**Institution:** Ashesi University  
**Date:** December 18, 2025

---

## Abstract

The AI Study Companion is a web-based educational platform that leverages Google's Gemini 2.0 API to automatically generate personalized study materials from uploaded documents. The system addresses the challenge of efficient study material creation by producing summaries, flashcards, and quizzes from PDF, DOCX, and TXT files. Built with PHP 8.x, MySQL, and vanilla JavaScript, the platform supports both student and teacher roles, enabling progress tracking, class management, and educational communication. This documentation covers the system's architecture, implementation decisions, security measures, and deployment procedures.

**Keywords:** Educational Technology, AI Integration, Web Development, Learning Management System

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [System Architecture](#2-system-architecture)
3. [Database Design](#3-database-design)
4. [Core Functionalities](#4-core-functionalities)
5. [AI Integration](#5-ai-integration)
6. [Security Implementation](#6-security-implementation)
7. [API Documentation](#7-api-documentation)
8. [Deployment Guide](#8-deployment-guide)
9. [Testing and Validation](#9-testing-and-validation)
10. [Conclusion](#10-conclusion)

---

## 1. Introduction

### 1.1 Project Purpose

Students often spend excessive time creating study materials rather than actually learning. The AI Study Companion automates this process by using artificial intelligence to generate educational content from uploaded documents, allowing students to focus on comprehension and retention.

### 1.2 Key Features

**For Students:**
- Upload documents (PDF, DOCX, TXT)
- AI-generated summaries, flashcards, and quizzes
- Progress tracking and performance analytics
- Enroll in teacher classes
- Direct messaging with teachers

**For Teachers:**
- Monitor student progress
- View class analytics
- Manage student enrollments
- Facilitate discussions

### 1.3 Technology Stack

| Component | Technology | Justification |
|-----------|-----------|---------------|
| Backend | PHP 8.x | Mature, well-documented, easy deployment |
| Database | MySQL 5.7+ | Reliable, widely supported |
| Frontend | HTML5/CSS3/JavaScript | No build pipeline needed, universal support |
| AI Service | Google Gemini 2.0 | Advanced NLP, generous free tier |
| PDF Parser | smalot/pdfparser | Pure PHP, no system dependencies |

---

## 2. System Architecture

### 2.1 Three-Tier Architecture

The system follows a classic MVC pattern with clear separation of concerns:

**Presentation Layer (Frontend)**
- HTML templates with PHP
- CSS for styling (responsive design)
- JavaScript for interactivity (AJAX, validation)

**Application Layer (Backend)**
- PHP classes handling business logic
- Object-oriented design with SOLID principles
- RESTful API endpoints

**Data Layer (Database)**
- MySQL database with normalized schema
- PDO for database abstraction
- Prepared statements for security

### 2.2 Request Flow Example

Student generates study materials:
1. JavaScript sends POST request to `/api/generate_materials.php`
2. Server validates session and permissions
3. Note content retrieved from database
4. AIProcessor sends text to Gemini API
5. Responses parsed and stored in database
6. JSON success response returned to client
7. Frontend updates to display materials

### 2.3 Key Design Decisions

**Why PHP over Node.js/Python?**
- Simpler deployment (no process managers)
- Lower hosting costs (shared hosting compatible)
- Extensive security documentation
- Familiar for educational contexts

**Why Vanilla JavaScript over React/Vue?**
- No build configuration complexity
- Faster initial page loads
- Easier debugging for learning
- Direct browser API access

---

## 3. Database Design

### 3.1 Core Tables

**users** - Authentication and profiles
```sql
- user_id (PK)
- email (UNIQUE)
- password_hash (bcrypt)
- role (ENUM: 'student', 'teacher')
- class_code (for teachers)
```

**notes** - Uploaded documents
```sql
- note_id (PK)
- user_id (FK)
- title
- content (extracted text)
- file_path
- file_type
```

**flashcards** - AI-generated flashcards
```sql
- flashcard_id (PK)
- note_id (FK → CASCADE DELETE)
- question
- answer
```

**quiz_questions** - Assessment questions
```sql
- question_id (PK)
- note_id (FK)
- question_text
- question_type (multiple_choice, short_answer)
- correct_answer
- options (A, B, C, D)
```

**quiz_attempts** - Student performance tracking
```sql
- attempt_id (PK)
- user_id (FK)
- note_id (FK)
- score
- correct_answers
- total_questions
```

**teacher_students** - Class enrollment (many-to-many)
```sql
- teacher_id (FK)
- student_id (FK)
- enrolled_date
- UNIQUE(teacher_id, student_id)
```

### 3.2 Key Relationships

- One user has many notes (1:N)
- One note has many flashcards, quizzes (1:N)
- Teachers and students have many-to-many relationship
- Cascade delete: Removing a note deletes all associated materials

### 3.3 Indexing Strategy

```sql
INDEX idx_email ON users(email)               -- Login lookups
INDEX idx_user_notes ON notes(user_id, upload_date DESC) -- Recent notes
INDEX idx_note_flashcards ON flashcards(note_id)        -- Material retrieval
UNIQUE KEY unique_enrollment ON teacher_students(teacher_id, student_id)
```

---

## 4. Core Functionalities

### 4.1 User Authentication

**Registration Process:**
```php
class User {
    public function register($full_name, $email, $password, $role) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Check password strength (min 6 chars)
        if (strlen($password) < 6) {
            return false;
        }
        
        // Hash password with bcrypt
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Generate unique class code for teachers
        $class_code = ($role === 'teacher') ? $this->generateUniqueClassCode() : null;
        
        // Insert into database
        $query = "INSERT INTO users (full_name, email, password_hash, role, class_code)
                  VALUES (:full_name, :email, :password_hash, :role, :class_code)";
        // Execute with prepared statement
    }
}
```

**Session Management:**
```php
session_start([
    'cookie_httponly' => true,    // Prevent JavaScript access
    'cookie_secure' => true,      // HTTPS only
    'cookie_samesite' => 'Strict' // CSRF protection
]);

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit();
    }
}
```

### 4.2 File Upload and Processing

**Upload Handler:**
```php
// Validate file
$allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
$max_size = 10 * 1024 * 1024; // 10MB

if (!in_array($file['type'], $allowed_types)) {
    return ['error' => 'Invalid file type'];
}

if ($file['size'] > $max_size) {
    return ['error' => 'File too large'];
}

// Store file securely
$unique_name = bin2hex(random_bytes(16)) . '.' . $extension;
$upload_path = '/uploads/' . $user_id . '/' . $unique_name;
move_uploaded_file($file['tmp_name'], $upload_path);
```

**Text Extraction:**
```php
class FileParser {
    public function extractText() {
        switch ($this->file_type) {
            case 'application/pdf':
                return $this->extractPDF();
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->extractDOCX();
            case 'text/plain':
                return file_get_contents($this->file_path);
        }
    }
    
    private function extractPDF() {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($this->file_path);
        return $pdf->getText();
    }
    
    private function extractDOCX() {
        $zip = new ZipArchive();
        $zip->open($this->file_path);
        $xml_content = $zip->getFromName('word/document.xml');
        $xml = new DOMDocument();
        $xml->loadXML($xml_content);
        
        // Extract all text nodes
        $text = '';
        foreach ($xml->getElementsByTagName('t') as $node) {
            $text .= $node->nodeValue . ' ';
        }
        return trim($text);
    }
}
```

### 4.3 Quiz Taking and Scoring

**Quiz Submission:**
```php
class Quiz {
    public function submitQuiz($note_id, $user_id, $answers) {
        // Get correct answers
        $questions = $this->getQuestions($note_id);
        
        $correct_count = 0;
        $total = count($questions);
        
        foreach ($questions as $question) {
            $user_answer = $answers[$question['question_id']] ?? '';
            
            if ($this->checkAnswer($question, $user_answer)) {
                $correct_count++;
            }
        }
        
        $score = ($correct_count / $total) * 100;
        
        // Store attempt
        $this->saveAttempt($user_id, $note_id, $score, $correct_count, $total);
        
        return [
            'score' => $score,
            'correct' => $correct_count,
            'total' => $total
        ];
    }
    
    private function checkAnswer($question, $user_answer) {
        if ($question['question_type'] === 'multiple_choice') {
            return strtolower(trim($user_answer)) === strtolower(trim($question['correct_answer']));
        } else {
            // Short answer: case-insensitive contains check
            return stripos($user_answer, $question['correct_answer']) !== false;
        }
    }
}
```

---

## 5. AI Integration

### 5.1 Google Gemini API Configuration

```php
class AIProcessor {
    private $apiKey = 'YOUR_API_KEY';
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    
    private function callGeminiAPI($prompt, $maxTokens = 2048) {
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => 0.7
            ]
        ];
        
        $ch = curl_init($this->apiUrl . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
}
```

### 5.2 Prompt Engineering

**Summary Generation:**
```php
$prompt = "You are an expert educational assistant. Create a comprehensive, 
detailed summary of the following text. Extract ALL key concepts, important 
facts, dates, names, and relationships. Make it thorough and educational.

Target length: approximately 500 words.

Text to summarize:
{$text}

Summary:";
```

**Flashcard Generation:**
```php
$prompt = "You are an expert educational content creator. Create exactly 15 
study flashcards with SPECIFIC questions and detailed answers based on the 
text below.

Format each flashcard as:
Q: [specific question]
A: [detailed answer]

Guidelines:
- Questions must use SPECIFIC facts, dates, names, or concepts from the text
- Avoid generic questions like 'What is the main idea?'
- Answers should be complete and educational
- Cover different aspects and difficulty levels

Text for flashcards:
{$text}

Generate EXACTLY 15 flashcards:";
```

**Quiz Generation:**
```php
$prompt = "You are an expert educational assessment designer. Create exactly 
10 quiz questions in JSON format based on the text below.

Mix of question types:
- 60% multiple choice (4 options)
- 40% short answer

JSON format for each question:
{
  \"question_text\": \"Question here\",
  \"question_type\": \"multiple_choice\" or \"short_answer\",
  \"correct_answer\": \"Answer\",
  \"options\": [\"A\", \"B\", \"C\", \"D\"] (null for short answer),
  \"explanation\": \"Why this is correct\"
}

Return a JSON array of 10 questions.

Text for quiz:
{$text}";
```

### 5.3 Response Parsing

**Flashcard Parser:**
```php
private function parseFlashcards($text) {
    $flashcards = [];
    $cards = preg_split('/\n\s*\n/', $text);
    
    foreach ($cards as $card) {
        if (preg_match('/Q:\s*(.+?)\n\s*A:\s*(.+)/is', $card, $matches)) {
            $flashcards[] = [
                'question' => trim($matches[1]),
                'answer' => trim($matches[2])
            ];
        }
    }
    
    return $flashcards;
}
```

**Quiz Parser:**
```php
private function parseQuizQuestions($text) {
    // Remove markdown code fences
    $text = preg_replace('/```json\s*|\s*```/', '', $text);
    
    $questions = json_decode($text, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        return $questions;
    }
    
    // Fallback: return empty array and log error
    error_log("Quiz parsing failed: " . json_last_error_msg());
    return [];
}
```

### 5.4 Error Handling

```php
public function generateSummary($text) {
    try {
        $response = $this->callGeminiAPI($prompt);
        return $this->extractTextFromResponse($response);
    } catch (Exception $e) {
        error_log("Summary generation failed: " . $e->getMessage());
        // Fallback: Extract first 5 sentences
        return $this->getFallbackSummary($text);
    }
}

private function getFallbackSummary($text) {
    $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    return implode(' ', array_slice($sentences, 0, 5));
}
```

---

## 6. Security Implementation

### 6.1 Password Security

**Hashing with Bcrypt:**
```php
// During registration
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// During login
if (password_verify($input_password, $stored_hash)) {
    // Check if rehashing needed
    if (password_needs_rehash($stored_hash, PASSWORD_BCRYPT, ['cost' => 12])) {
        $new_hash = password_hash($input_password, PASSWORD_BCRYPT, ['cost' => 12]);
        // Update database
    }
}
```

**Key Points:**
- Bcrypt automatically handles salting
- Cost factor of 12 = 4,096 iterations
- Future-proof: can increase cost as hardware improves

### 6.2 SQL Injection Prevention

**Always use prepared statements:**
```php
// CORRECT
$query = "SELECT * FROM users WHERE email = :email";
$stmt = $conn->prepare($query);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

// NEVER do this
// $query = "SELECT * FROM users WHERE email = '$email'";
```

### 6.3 XSS Prevention

**Output encoding:**
```php
// In HTML
<h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>

// In JavaScript
<script>
const userName = <?php echo json_encode($user_name, JSON_HEX_TAG); ?>;
</script>
```

### 6.4 CSRF Protection

**Token-based protection:**
```php
// Generate token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Include in forms
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// Validate on submission
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF validation failed');
}
```

### 6.5 File Upload Security

**Validation layers:**
```php
// 1. Check file size
if ($file['size'] > 10 * 1024 * 1024) {
    return ['error' => 'File too large'];
}

// 2. Validate MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
$allowed = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];

if (!in_array($mime, $allowed)) {
    return ['error' => 'Invalid file type'];
}

// 3. Validate extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'docx', 'txt'])) {
    return ['error' => 'Invalid extension'];
}

// 4. Generate unique filename (prevent overwrites)
$unique_name = bin2hex(random_bytes(16)) . '.' . $ext;

// 5. Store outside web root or with .htaccess protection
```

---

## 7. API Documentation

### 7.1 Authentication Endpoints

**POST /auth/process_auth.php?action=register**

Request:
```json
{
    "full_name": "Jane Doe",
    "email": "jane@example.com",
    "password": "SecurePass123",
    "role": "student"
}
```

Response (200):
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user_id": 42,
        "redirect": "/dashboard/index.php"
    }
}
```

**POST /auth/process_auth.php?action=login**

Request:
```json
{
    "email": "jane@example.com",
    "password": "SecurePass123"
}
```

Response (200):
```json
{
    "success": true,
    "data": {
        "user_id": 42,
        "role": "student",
        "redirect": "/dashboard/index.php"
    }
}
```

### 7.2 Note Management

**POST /api/upload_note.php**

Request (multipart/form-data):
- title: "Biology Chapter 1"
- file: [PDF/DOCX/TXT file]

Response (200):
```json
{
    "success": true,
    "data": {
        "note_id": 123,
        "title": "Biology Chapter 1",
        "file_type": "application/pdf",
        "content_length": 5420
    }
}
```

**DELETE /api/delete_note.php**

Request:
```json
{
    "note_id": 123
}
```

### 7.3 AI Generation

**POST /api/generate_materials.php**

Request:
```json
{
    "note_id": 123
}
```

Response (200):
```json
{
    "success": true,
    "data": {
        "summary_id": 456,
        "flashcard_count": 15,
        "quiz_question_count": 10
    }
}
```

Processing time: 30-60 seconds

### 7.4 Quiz Submission

**POST /api/submit_quiz.php**

Request:
```json
{
    "note_id": 123,
    "answers": [
        {"question_id": 1, "answer": "A"},
        {"question_id": 2, "answer": "Mitosis produces two identical cells"}
    ]
}
```

Response (200):
```json
{
    "success": true,
    "data": {
        "score": 85.0,
        "correct_answers": 8,
        "total_questions": 10,
        "attempt_id": 999
    }
}
```

### 7.5 Class Management

**POST /api/join_class.php**

Request:
```json
{
    "class_code": "ABC123"
}
```

Response (200):
```json
{
    "success": true,
    "data": {
        "teacher_id": 5,
        "teacher_name": "Dr. Smith"
    }
}
```

---

## 8. Deployment Guide

### 8.1 Server Requirements

**Minimum Specifications:**
- Apache 2.4+
- PHP 8.0+ with extensions: pdo_mysql, curl, mbstring, json
- MySQL 5.7+ or MariaDB 10.3+
- 2GB RAM
- 10GB storage

### 8.2 Installation Steps

**1. Clone Repository**
```bash
cd /var/www/html
git clone https://github.com/Gertrude07/ai-study-companion.git
cd ai-study-companion
```

**2. Configure Database**
```bash
mysql -u root -p
CREATE DATABASE ai_study_companion;
CREATE USER 'studyapp'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ai_study_companion.* TO 'studyapp'@'localhost';
FLUSH PRIVILEGES;
EXIT;

mysql -u studyapp -p ai_study_companion < webtech_2025A_gertrude_akagbo.sql
```

**3. Configure Application**

Copy sample configs:
```bash
cp config/database.sample.php config/database.php
cp config/ai_config.sample.php config/ai_config.php
```

Edit `config/database.php`:
```php
return [
    'host' => 'localhost',
    'database' => 'ai_study_companion',
    'username' => 'studyapp',
    'password' => 'your_password'
];
```

Edit `config/ai_config.php`:
```php
return [
    'api_key' => 'your-gemini-api-key',
    'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
    'model' => 'gemini-2.0-flash',
    'max_tokens' => 2048
];
```

**4. Set Permissions**
```bash
chmod 755 uploads
chmod 600 config/*.php
chown -R www-data:www-data /var/www/html/ai-study-companion
```

**5. Install Dependencies**
```bash
php composer.phar install
```

**6. Configure Apache**

Create `/etc/apache2/sites-available/ai-study-companion.conf`:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/ai-study-companion
    
    <Directory /var/www/html/ai-study-companion>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Enable site:
```bash
a2ensite ai-study-companion
systemctl reload apache2
```

**7. Enable HTTPS (Recommended)**
```bash
apt install certbot python3-certbot-apache
certbot --apache -d yourdomain.com
```

### 8.3 Environment-Specific Configuration

**Development:**
```php
// Enable error display
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Production:**
```php
// Disable error display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');
```

---

## 9. Testing and Validation

### 9.1 Manual Testing Checklist

**Authentication:**
- [ ] User can register with valid credentials
- [ ] Registration fails with invalid email
- [ ] Registration fails with short password
- [ ] User can login with correct credentials
- [ ] Login fails with incorrect password
- [ ] Session persists across pages
- [ ] Logout clears session

**File Upload:**
- [ ] PDF files upload successfully
- [ ] DOCX files upload successfully
- [ ] TXT files upload successfully
- [ ] Files over 10MB are rejected
- [ ] Invalid file types are rejected
- [ ] Text is extracted correctly from PDFs
- [ ] Text is extracted correctly from DOCX

**AI Generation:**
- [ ] Summary generates within 60 seconds
- [ ] Summary is relevant to document content
- [ ] Exactly 15 flashcards are generated
- [ ] Flashcards contain specific facts
- [ ] Quiz generates 10 questions
- [ ] Quiz includes multiple choice and short answer
- [ ] Materials can be regenerated

**Quiz Taking:**
- [ ] Multiple choice answers are graded correctly
- [ ] Short answer accepts reasonable variations
- [ ] Score is calculated accurately
- [ ] Attempt is saved to history
- [ ] Previous attempts are viewable

**Class Management:**
- [ ] Teacher receives unique class code
- [ ] Student can join class with valid code
- [ ] Invalid class code shows error
- [ ] Student cannot join same class twice
- [ ] Teacher can view enrolled students
- [ ] Teacher can remove students
- [ ] Student can leave class

### 9.2 Security Testing

**SQL Injection Tests:**
```sql
-- Test login with SQL injection
email: admin' OR '1'='1
password: anything

-- Expected: Login fails, no error message reveals SQL
```

**XSS Tests:**
```javascript
// Test note title with script
title: <script>alert('XSS')</script>

// Expected: Script is escaped, displayed as text
```

**CSRF Tests:**
- Submit form without CSRF token → Should fail
- Submit form with invalid token → Should fail
- Submit form with valid token → Should succeed

### 9.3 Performance Benchmarks

**Target Metrics:**
- Page load time: < 3 seconds
- Database query time: < 500ms
- AI generation time: 30-60 seconds
- File upload time: < 10 seconds for 10MB file
- Concurrent users: Support 50+ simultaneous users

**Testing Tools:**
- Apache Bench for load testing
- Chrome DevTools for frontend performance
- MySQL slow query log for database optimization

---

## 10. Conclusion

### 10.1 Project Achievements

The AI Study Companion successfully demonstrates the integration of modern AI capabilities into a practical educational platform. Key accomplishments include:

1. **Automated Content Generation:** Successfully implemented AI-powered generation of summaries, flashcards, and quizzes with consistent quality
2. **Full-Stack Implementation:** Built complete system from database design through frontend user experience
3. **Security Best Practices:** Implemented comprehensive security measures including password hashing, SQL injection prevention, and CSRF protection
4. **Scalable Architecture:** Designed system with clear separation of concerns enabling future enhancements
5. **User-Friendly Interface:** Created intuitive interfaces for both student and teacher roles

### 10.2 Technical Learnings

**What Worked Well:**
- Object-oriented PHP design improved code maintainability
- Prepared statements eliminated SQL injection vulnerabilities
- Prompt engineering significantly improved AI output quality
- Three-tier architecture simplified testing and debugging
- Vanilla JavaScript reduced complexity and improved performance

**Challenges Overcome:**
- PDF text extraction accuracy issues with complex layouts
- AI response parsing required multiple fallback strategies
- Session management across different server configurations
- Balancing security with user experience

### 10.3 Future Enhancements

**Short-term (3-6 months):**
- Real-time quiz score visualization
- Email notifications for messages
- Export flashcards to Anki/Quizlet format
- Mobile-responsive improvements

**Medium-term (6-12 months):**
- Spaced repetition algorithm for flashcard review
- Voice-to-text for quiz answers
- Integration with Google Classroom
- Advanced analytics dashboard

**Long-term (12+ months):**
- Native mobile applications (iOS/Android)
- Multi-language support
- Video content processing
- Collaborative note-taking features

### 10.4 Lessons for Future Projects

1. **Start with Security:** Implementing security from the beginning is easier than retrofitting
2. **Document as You Go:** Writing documentation alongside code improves clarity
3. **Test Early, Test Often:** Early testing catches issues before they become complex
4. **Keep It Simple:** Vanilla technologies often provide better results than complex frameworks
5. **User Feedback:** Regular user testing reveals issues that aren't apparent to developers

### 10.5 Final Thoughts

This project demonstrates that integrating advanced AI capabilities into educational technology doesn't require complex infrastructure or expensive services. With careful design, attention to security, and focus on user needs, powerful learning tools can be built using accessible technologies.

The AI Study Companion serves as both a functional educational platform and a comprehensive reference implementation for future web development projects, showcasing best practices in full-stack development, API integration, and security implementation.

---

## Appendix A: Complete File Structure

```
ai-study-companion/
├── api/                          # API endpoints
│   ├── delete_account.php
│   ├── delete_note.php
│   ├── generate_materials.php
│   ├── generate_more.php
│   ├── get_clarification.php
│   ├── get_messages.php
│   ├── join_class.php
│   ├── send_message.php
│   ├── submit_quiz.php
│   └── upload_note.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── dashboard.css
│   └── js/
│       ├── ajax-handler.js
│       └── validation.js
├── auth/
│   ├── login.php
│   ├── signup.php
│   ├── logout.php
│   └── process_auth.php
├── classes/
│   ├── Database.php
│   ├── User.php
│   ├── Note.php
│   ├── AIProcessor.php
│   ├── FileParser.php
│   ├── Quiz.php
│   ├── Teacher.php
│   └── Analytics.php
├── config/
│   ├── database.php
│   ├── database.sample.php
│   ├── ai_config.php
│   └── ai_config.sample.php
├── dashboard/
│   ├── index.php
│   ├── upload.php
│   ├── materials.php
│   ├── flashcards.php
│   ├── quiz.php
│   ├── progress.php
│   └── teacher/
│       ├── index.php
│       └── analytics.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── uploads/                      # User-uploaded files
├── vendor/                       # Composer dependencies
├── composer.json
├── README.md
└── index.php
```

## Appendix B: Key Configuration Files

**composer.json**
```json
{
    "require": {
        "smalot/pdfparser": "^2.0"
    }
}
```

**.htaccess** (Root directory)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-XSS-Protection "1; mode=block"
Header set X-Frame-Options "DENY"
```

**uploads/.htaccess** (Prevent direct access)
```apache
Order Deny,Allow
Deny from all
```

## Appendix C: Common Troubleshooting

**Problem:** Database connection fails  
**Solution:** Check credentials in `config/database.php`, verify MySQL service is running

**Problem:** File upload fails  
**Solution:** Check `upload_max_filesize` and `post_max_size` in `php.ini`

**Problem:** AI generation returns empty results  
**Solution:** Verify API key is correct, check error logs for rate limiting

**Problem:** Session expires too quickly  
**Solution:** Adjust `session.gc_maxlifetime` in PHP configuration

**Problem:** PDF text extraction garbled  
**Solution:** PDF may have complex layout; try converting to plain text first

---

**Repository:** https://github.com/Gertrude07/ai-study-companion  
**Contact:** Gertrude Akagbo | Ashesi University | gertrude.akagbo@ashesi.edu.gh  
**Last Updated:** December 18, 2025
