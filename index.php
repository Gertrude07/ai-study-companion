<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard/index.php');
    exit();
}

$pageTitle = 'Home';
$cssPath = 'assets/css/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Study Companion - Transform Your Learning</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #000000 0%, #1A1A1A 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(8, 145, 178, 0.2) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(20, 184, 166, 0.2) 0%, transparent 50%);
            animation: heroGlow 8s ease-in-out infinite;
        }
        
        @keyframes heroGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF 0%, #06B6D4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 0 30px rgba(8, 145, 178, 0.8));
        }
        
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 1;
            color: #FFFFFF;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .features {
            padding: 60px 20px;
            background: linear-gradient(180deg, #000000 0%, #1A1A1A 100%);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 12px;
            background: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(8, 145, 178, 0.5);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0891B2 0%, #22D3EE 100%);
            transform: scaleX(0);
            transition: transform 0.4s ease;
            box-shadow: 0 0 15px rgba(8, 145, 178, 0.8);
        }
        
        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 16px 50px rgba(8, 145, 178, 0.6);
            border-color: #0891B2;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            font-size: 3rem;
            background: linear-gradient(135deg, #06B6D4 0%, #22D3EE 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 30px rgba(8, 145, 178, 0.9));
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #FFFFFF;
        }
        
        .feature-description {
            color: #F1F5F9;
            line-height: 1.6;
            font-weight: 500;
        }
        
        .how-it-works {
            padding: 60px 20px;
            background: linear-gradient(180deg, #1A1A1A 0%, #000000 100%);
            position: relative;
        }
        
        .how-it-works::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(8, 145, 178, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .steps {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .step {
            display: flex;
            gap: 2rem;
            margin-bottom: 3rem;
            align-items: center;
        }
        
        .step-number {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0891B2 0%, #22D3EE 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            box-shadow: 0 6px 30px rgba(8, 145, 178, 0.8);
            position: relative;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .step-number::before {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0891B2, #22D3EE);
            z-index: -1;
            opacity: 0.7;
            filter: blur(15px);
        }
        
        .step-content h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #FFFFFF;
            font-weight: 700;
        }
        
        .step-content p {
            color: #F1F5F9;
            font-weight: 500;
        }
        
        .cta {
            padding: 80px 20px;
            background: linear-gradient(135deg, #000000 0%, #1A1A1A 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(8, 145, 178, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(20, 184, 166, 0.3) 0%, transparent 50%);
            animation: ctaGlow 6s ease-in-out infinite;
        }
        
        @keyframes ctaGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF 0%, #06B6D4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 0 30px rgba(8, 145, 178, 0.8));
        }
        
        .cta p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 1;
            color: #FFFFFF;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .cta h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1><i class="fas fa-graduation-cap"></i> AI Study Companion</h1>
            <p>Transform your study notes into interactive learning materials with the power of AI</p>
            <div class="hero-buttons">
                <a href="auth/signup.php" class="btn btn-lg" style="background: linear-gradient(135deg, #0891B2 0%, #22D3EE 100%); color: white; font-weight: 600; box-shadow: 0 8px 32px rgba(8, 145, 178, 0.5); position: relative; z-index: 1;">
                    <i class="fas fa-rocket"></i> Get Started Free
                </a>
                <a href="auth/login.php" class="btn btn-outline btn-lg" style="border-color: #0891B2; color: #06B6D4; background: rgba(26, 26, 26, 0.5); backdrop-filter: blur(10px); font-weight: 600; position: relative; z-index: 1;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #111827;">
                Supercharge Your Learning
            </h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h3 class="feature-title">Easy Upload</h3>
                    <p class="feature-description">
                        Upload your notes in PDF, DOCX, or TXT format. Our system automatically extracts and processes the content.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="feature-title">AI-Powered Summaries</h3>
                    <p class="feature-description">
                        Get concise summaries highlighting key concepts and main ideas from your study materials.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3 class="feature-title">Interactive Flashcards</h3>
                    <p class="feature-description">
                        Study with automatically generated flashcards featuring questions and answers for active recall.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 class="feature-title">Smart Quizzes</h3>
                    <p class="feature-description">
                        Test your knowledge with AI-generated quizzes including multiple choice and short answer questions.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Progress Tracking</h3>
                    <p class="feature-description">
                        Monitor your learning journey with detailed analytics and identify areas that need more focus.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Study Anywhere</h3>
                    <p class="feature-description">
                        Fully responsive design lets you study on your phone, tablet, or computer. Learn on the go!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #111827;">
                How It Works
            </h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Upload Your Notes</h3>
                        <p>
                            Create an account and upload your study notes. We support PDF, DOCX, and plain text files up to 10MB.
                        </p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>AI Generates Materials</h3>
                        <p>
                            Our AI analyzes your notes and automatically creates summaries, flashcards, and quiz questions tailored to your content.
                        </p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Study & Practice</h3>
                        <p>
                            Review summaries, flip through flashcards, and take quizzes to reinforce your understanding of the material.
                        </p>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Track Progress</h3>
                        <p>
                            View your analytics dashboard to see quiz scores, study streaks, and identify topics that need more attention.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Transform <span style="background: linear-gradient(135deg, #0891B2 0%, #22D3EE 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Your Learning?</span></h2>
            <p>Join thousands of students who are studying smarter with AI-powered tools</p>
            <a href="auth/signup.php" class="btn btn-lg" style="background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%); color: #000000; font-weight: 700; border: 2px solid rgba(8, 145, 178, 0.3); backdrop-filter: blur(10px); box-shadow: 0 8px 32px rgba(8, 145, 178, 0.3);">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> AI Study Companion. Built for Web Technologies Summer 2025.</p>
            <p style="margin-top: 0.5rem; opacity: 0.8;">
                <i class="fas fa-code"></i> Crafted with passion for education
            </p>
        </div>
    </footer>
</body>
</html>
