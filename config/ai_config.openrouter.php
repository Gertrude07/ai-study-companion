<?php
/**
 * AI API Configuration for OpenRouter (Free Models)
 * These models have generous free tiers
 */

// ========== OPENROUTER API CONFIGURATION ==========
// Get your free API key at: https://openrouter.ai/keys
define('AI_API_KEY', 'YOUR_OPENROUTER_API_KEY_HERE');

// OpenRouter API endpoint
define('AI_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

// Primary Model - Free and reliable
define('AI_MODEL', 'google/gemini-2.0-flash-exp:free');

// Maximum tokens for responses
define('AI_MAX_TOKENS', 2000);

// Backup API Keys (optional)
define('AI_BACKUP_KEYS', []);

// Fallback Models - All free on OpenRouter
define('AI_FALLBACK_MODELS', [
    'qwen/qwen-2-7b-instruct:free',
    'microsoft/phi-3-mini-128k-instruct:free',
    'meta-llama/llama-3.2-3b-instruct:free'
]);

// ========== AI PROMPTS ==========

define(
    'SUMMARY_PROMPT',
    'You are an expert academic assistant. Create a COMPREHENSIVE, DETAILED summary of the student\'s uploaded text.

REQUIREMENTS:
- Extract ALL key concepts, facts, dates, names, and definitions
- Organize information logically with clear sections
- Write 300-500 words covering EVERYTHING important
- Use bullet points, numbered lists, and headings for clarity
- Focus ONLY on information actually present in the text

FORMAT:
Use markdown formatting:
- **Bold** for key terms and concepts
- Use headings (###) to organize sections
- Use bullet points for lists'
);

define(
    'FLASHCARD_PROMPT',
    'You are creating study flashcards for a student. Extract SPECIFIC FACTS from the provided text.

CRITICAL RULES:
- Use ACTUAL facts, dates, names, concepts from the text
- NO generic questions like "What is important?"
- Questions should be specific and testable
- Answers should be detailed (2-4 sentences)

EXAMPLES:
Q: What is photosynthesis?
A: Photosynthesis is the process by which green plants use sunlight, water, and carbon dioxide to produce glucose and oxygen.'
);

define(
    'QUIZ_PROMPT',
    'Create 10 quiz questions based ONLY on the provided text.

REQUIREMENTS:
- 60% multiple choice, 40% short answer
- Multiple choice: 4 options each, only 1 correct
- NO generic questions - be specific to the text

OUTPUT FORMAT - JSON ONLY:
[
  {
    "question_text": "What is the primary function of mitochondria?",
    "question_type": "multiple_choice",
    "correct_answer": "Producing ATP through cellular respiration",
    "options": ["Producing ATP", "Storing DNA", "Synthesizing proteins", "Breaking down waste"]
  },
  {
    "question_text": "Explain cellular respiration.",
    "question_type": "short_answer",
    "correct_answer": "Cellular respiration breaks down glucose to produce ATP energy."
  }
]'
);
?>