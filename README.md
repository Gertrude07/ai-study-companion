# AI Study Companion

## Overview

AI Study Companion is an intelligent web-based learning platform designed to enhance student study efficiency through artificial intelligence. The system automatically generates personalized study materials including summaries, flashcards, and quiz questions from uploaded documents, facilitating active learning and knowledge retention.

## Live Application

The application is currently hosted at:
**http://169.239.251.102:341/~gertrude.akagbo/ai-study-companion/**

## Features

### Core Functionality

- **Document Upload and Processing**: Supports multiple file formats including PDF, DOCX, and TXT
- **AI-Powered Study Material Generation**:
  - Comprehensive summaries of uploaded content
  - Interactive flashcards for spaced repetition learning
  - Customizable quiz questions (multiple choice and short answer)
- **Progress Analytics**: Track study sessions, quiz performance, and learning streaks
- **Real-Time Messaging**: Communication system between students and teachers
- **Class Discussion Forums**: Collaborative learning environment

### User Roles

#### Students
- Upload and manage study materials
- Generate AI-powered learning resources
- Take quizzes and track performance
- Communicate with enrolled teachers
- Participate in class discussions
- Enroll in multiple classes
- Leave classes as needed

#### Teachers
- Monitor student progress and analytics
- View detailed student performance metrics
- Manage class enrollments
- Remove students from classes
- Participate in class discussions
- Generate unique class codes for student enrollment

## Technology Stack

### Backend
- **PHP 8.x**: Server-side logic and API endpoints
- **MySQL**: Database management system
- **PDO**: Database abstraction layer

### Frontend
- **HTML5/CSS3**: Structure and styling
- **JavaScript (ES6+)**: Client-side interactivity
- **AJAX**: Asynchronous data communication

### AI Integration
- **Google Gemini API**: Natural language processing and content generation
- **Custom AI Processor**: Handles prompt engineering and response parsing

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)
- Google Gemini API key

### Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/Gertrude07/ai-study-companion.git
   cd ai-study-companion
   ```

2. Import the database:
   ```bash
   mysql -u your_username -p your_database < webtech_2025A_gertrude_akagbo.sql
   ```

3. Configure database connection:
   - Update `config/database.php` with your database credentials

4. Configure AI API:
   - Create `config/ai_config.php`
   - Add your Google Gemini API key:
     ```php
     <?php
     define('AI_API_KEY', 'your-api-key-here');
     define('AI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');
     define('AI_MODEL', 'gemini-2.0-flash');
     ?>
     ```

5. Set proper permissions:
   ```bash
   chmod 755 uploads
   chmod 600 config/ai_config.php
   ```

6. Access the application via your web server

## Database Schema

The application utilizes a relational database with the following key tables:

- `users`: User authentication and profile information
- `notes`: Uploaded study materials and metadata
- `flashcards`: AI-generated flashcard content
- `quiz_questions`: Generated quiz questions and answers
- `quiz_attempts`: Student quiz history and scores
- `teacher_students`: Class enrollment relationships
- `messages`: Communication between users
- `discussions`: Forum posts and replies

## Security Features

- Session-based authentication with HTTP-only cookies
- SQL injection prevention via prepared statements
- Role-based access control (RBAC)
- Secure file upload validation
- Password hashing using bcrypt

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/join_class.php` | POST | Student enrollment in teacher class |
| `/api/leave_class.php` | POST | Student leaves a class |
| `/api/remove_student.php` | POST | Teacher removes student from class |
| `/api/get_my_teacher.php` | GET | Retrieve student's enrolled teachers |
| `/api/send_message.php` | POST | Send message to user |
| `/api/get_messages.php` | GET | Retrieve conversation messages |
| `/api/get_clarification.php` | POST | Request AI explanation |

## Usage

### For Students

1. Register an account with a valid email address
2. Log in to access the student dashboard
3. Upload study materials in supported formats
4. Generate AI-powered study resources
5. Complete quizzes to test knowledge retention
6. Track progress through analytics dashboard
7. Enroll in classes using teacher-provided codes

### For Teachers

1. Register an account and verify email
2. Access the teacher dashboard
3. Share class code with students
4. Monitor student progress and performance
5. View detailed analytics for enrolled students
6. Manage class enrollments as needed

## Contributing

This project was developed as part of the WEBTECH 2025A course curriculum. Contributions and improvements are welcome through pull requests.

## License

This project is developed for educational purposes as part of academic coursework.

## Author

Gertrude Akagbo

## Acknowledgments

- Google Gemini API for AI capabilities
- Ashesi University Computer Science Department
- WEBTECH 2025A Course Instructors
