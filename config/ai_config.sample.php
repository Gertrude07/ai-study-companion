<?php
/**
 * AI API Configuration
 * UPDATED: Fixed broken free models that caused "No endpoints found" errors
 * Copy this to ai_config.php and add your API key
 */

// OpenRouter API Configuration
define('AI_API_KEY', 'YOUR_OPENROUTER_API_KEY_HERE');
define('AI_API_URL', 'https://openrouter.ai/api/v1/chat/completions');

// Primary Model - Fast and reliable free model
define('AI_MODEL', 'meta-llama/llama-3.2-3b-instruct:free');

// Maximum tokens for responses
define('AI_MAX_TOKENS', 2000);

// BACKUP API Keys (optional - add multiple keys for redundancy)
define('AI_BACKUP_KEYS', [
    // Add backup keys here if you have multiple OpenRouter accounts
    // 'sk-or-v1-second-key-here',
    // 'sk-or-v1-third-key-here',
]);

// FALLBACK MODELS - Updated with currently working free models
define('AI_FALLBACK_MODELS', [
    'google/gemini-2.0-flash-exp:free',      // Google's latest fast model
    'qwen/qwen-2-7b-instruct:free',          // Alibaba's Qwen model
    'microsoft/phi-3-mini-128k-instruct:free', // Microsoft's Phi-3
    'nousresearch/hermes-3-llama-3.1-405b:free' // Nous Research Hermes
]);

/**
 * AI PROMPTS - These define how the AI generates study materials
 */

// Summary Generation Prompt
define(
    'SUMMARY_PROMPT',
    'You are an expert academic assistant. Create a COMPREHENSIVE, DETAILED summary of the student\'s uploaded text.

REQUIREMENTS:
✓ Extract ALL key concepts, facts, dates, names, and definitions
✓ Organize information logically with clear sections
✓ Write 300-500 words covering EVERYTHING important
✓ Use bullet points, numbered lists, and headings for clarity
✓ Focus ONLY on information actually present in the text
✓ DO NOT add external information or make assumptions

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
✓ Use ACTUAL facts, dates, names, concepts from the text
✓ NO generic questions like "What is important?"
✓ Questions should be specific and testable
✓ Answers should be detailed (2-4 sentences)
✓ Cover different topics/sections from the text
✓ Mix question types: definitions, explanations, comparisons, applications

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
✓ 60% multiple choice, 40% short answer
✓ Questions must test understanding of ACTUAL content from the text
✓ Multiple choice: 4 options each, only 1 correct
✓ Cover different topics/sections from the material
✓ NO generic questions - be specific to the text

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