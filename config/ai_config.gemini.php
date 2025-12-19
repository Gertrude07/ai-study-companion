<?php
/**
 * AI API Configuration for Google Gemini
 * Copy this file to ai_config.php and add your Gemini API key
 * 
 * To get a Gemini API key: https://aistudio.google.com/app/apikey
 */

// ========== GEMINI API CONFIGURATION ==========
// Replace with your actual Gemini API key
define('AI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

// Gemini 2.0 Flash endpoint (fast and free)
define('AI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

// Model name
define('AI_MODEL', 'gemini-2.0-flash');

// Maximum tokens for responses
define('AI_MAX_TOKENS', 2048);

// ========== AI PROMPTS ==========

// Summary Generation Prompt
define(
    'SUMMARY_PROMPT',
    'You are an expert academic assistant. Create a COMPREHENSIVE, DETAILED summary of the student\'s uploaded text.

REQUIREMENTS:
- Extract ALL key concepts, facts, dates, names, and definitions
- Organize information logically with clear sections
- Write 300-500 words covering EVERYTHING important
- Use bullet points, numbered lists, and headings for clarity
- Focus ONLY on information actually present in the text
- DO NOT add external information or make assumptions

FORMAT:
Use markdown formatting:
- **Bold** for key terms and concepts
- Use headings (###) to organize sections
- Use bullet points for lists
- Use numbered lists for sequential information'
);

// Flashcard Generation Prompt  
define(
    'FLASHCARD_PROMPT',
    'You are creating study flashcards for a student. Extract SPECIFIC FACTS from the provided text to create targeted flashcards.

CRITICAL RULES:
- Use ACTUAL facts, dates, names, concepts from the text
- NO generic questions like "What is important?"
- Questions should be specific and testable
- Answers should be detailed (2-4 sentences)
- Cover different topics/sections from the text
- Mix question types: definitions, explanations, comparisons, applications

EXAMPLES OF GOOD FLASHCARDS:
Q: What is photosynthesis?
A: Photosynthesis is the process by which green plants use sunlight, water, and carbon dioxide to produce glucose and oxygen. It occurs in chloroplasts and involves light-dependent and light-independent reactions.

Q: Who developed the theory of relativity and in what year?
A: Albert Einstein developed the theory of relativity, with special relativity published in 1905 and general relativity in 1915.'
);

// Quiz Generation Prompt
define(
    'QUIZ_PROMPT',
    'You are creating quiz questions for a student. Generate 10 questions based ONLY on the provided text.

REQUIREMENTS:
- 60% multiple choice, 40% short answer
- Questions must test understanding of ACTUAL content from the text
- Multiple choice: 4 options each, only 1 correct
- Cover different topics/sections from the material
- NO generic questions - be specific to the text

OUTPUT FORMAT - JSON ONLY (no other text):
[
  {
    "question_text": "What is the primary function of mitochondria?",
    "question_type": "multiple_choice",
    "correct_answer": "Producing ATP through cellular respiration",
    "options": [
      "Producing ATP through cellular respiration",
      "Storing genetic material",
      "Synthesizing proteins",
      "Breaking down waste materials"
    ]
  },
  {
    "question_text": "Explain the process of cellular respiration in your own words.",
    "question_type": "short_answer",
    "correct_answer": "Cellular respiration is the process by which cells break down glucose to produce ATP (energy). It involves glycolysis, the Krebs cycle, and the electron transport chain."
  }
]'
);
?>