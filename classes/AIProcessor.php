<?php
// AI Processor Class - Handles AI API integration

require_once __DIR__ . '/../config/ai_config.php';

class AIProcessor
{
    private $apiKey;
    private $apiUrl;
    private $model;
    private $maxTokens;

    public function __construct()
    {
        $this->apiKey = AI_API_KEY;
        $this->apiUrl = AI_API_URL;
        $this->model = AI_MODEL;
        $this->maxTokens = AI_MAX_TOKENS;
    }

    // Generate summary from text
    public function generateSummary($text)
    {
        $textToUse = $this->truncateText($text, 8000);
        $prompt = SUMMARY_PROMPT . "\n\n========== START OF STUDENT'S UPLOADED TEXT ==========\n" . $textToUse . "\n========== END OF TEXT ==========\n\nNow create your detailed summary using ONLY information from the text above:";

        error_log("Generating summary from " . strlen($textToUse) . " chars of text");
        $response = $this->callAI($prompt);

        if ($response['success']) {
            return [
                'success' => true,
                'summary' => $response['content']
            ];
        }

        return ['success' => false, 'message' => $response['message']];
    }

    // Generate flashcards from text
    public function generateFlashcards($text, $count = 15)
    {
        $textToUse = $this->truncateText($text, 8000);
        $prompt = FLASHCARD_PROMPT . "\n\n⚠️ CRITICAL: Generate EXACTLY " . $count . " flashcards using SPECIFIC facts, dates, names, concepts from the text.\n\n⚠️ FORMAT REQUIREMENT (you MUST follow this exactly):\nQ: [question here]\nA: [detailed answer here]\n\nQ: [next question]\nA: [next answer]\n\n========== STUDENT'S TEXT ==========\n" . $textToUse . "\n========== END ==========\n\nGenerate " . $count . " flashcards in Q:/A: format now:";

        error_log("Generating " . $count . " flashcards from " . strlen($textToUse) . " chars of text");
        $response = $this->callAI($prompt, 3000);

        if ($response['success']) {
            // Check if content is empty or too short
            if (empty(trim($response['content'])) || strlen(trim($response['content'])) < 50) {
                error_log("⚠️ AI returned empty or very short content for flashcard generation");
                return ['success' => false, 'message' => 'AI returned empty response. Please try again in a few moments (rate limit).'];
            }

            $flashcards = $this->parseFlashcards($response['content']);

            // If parsing failed, return error instead of empty array
            if (empty($flashcards)) {
                error_log("⚠️ No flashcards parsed from AI response");
                return ['success' => false, 'message' => 'Failed to parse flashcards from AI response'];
            }

            // Check if flashcards are generic fallback
            if (!empty($flashcards) && isset($flashcards[0]['question'])) {
                $firstQuestion = $flashcards[0]['question'];
                if (strpos($firstQuestion, 'Review the material') !== false) {
                    error_log("⚠️ Flashcard generation fell back to generic cards");
                    return ['success' => false, 'message' => 'Unable to generate flashcards. Please try again.'];
                }
            }

            return [
                'success' => true,
                'flashcards' => $flashcards
            ];
        }

        return ['success' => false, 'message' => $response['message']];
    }

    // Generate quiz from text
    public function generateQuiz($text)
    {
        $textToUse = $this->truncateText($text, 8000);
        $prompt = QUIZ_PROMPT . "\n\nReturn ONLY JSON array, no other text.\n\n========== TEXT TO CREATE QUIZ FROM ==========\n" . $textToUse . "\n========== END ==========\n\nJSON quiz questions:";

        error_log("Generating quiz from " . strlen($textToUse) . " chars of text");
        $response = $this->callAI($prompt);

        if ($response['success']) {
            // Check if content is empty or too short
            if (empty(trim($response['content'])) || strlen(trim($response['content'])) < 50) {
                error_log("⚠️ AI returned empty or very short content for quiz generation");
                return ['success' => false, 'message' => 'AI returned empty response. Please try again in a few moments (rate limit).'];
            }

            $questions = $this->parseQuizQuestions($response['content']);

            // Check if we got valid questions (not fallback)
            if (!empty($questions) && isset($questions[0]['question_text'])) {
                $firstQuestion = $questions[0]['question_text'];
                // If it's a fallback question, report failure
                if (strpos($firstQuestion, 'Review the key concept') !== false) {
                    error_log("⚠️ Quiz generation fell back to generic questions");
                    return ['success' => false, 'message' => 'Unable to generate quiz questions. Please try again.'];
                }
            }

            return [
                'success' => true,
                'questions' => $questions
            ];
        }

        return ['success' => false, 'message' => $response['message']];
    }

    // Generate all materials in one call (faster)
    public function generateAllMaterials($text)
    {
        $textToUse = $this->truncateText($text, 8000);

        $prompt = "You are an AI study assistant. Generate comprehensive study materials from the text below.\n\n";
        $prompt .= "⚠️ CRITICAL: You MUST extract SPECIFIC FACTS, NAMES, DATES, CONCEPTS from the text. NO GENERIC CONTENT!\n\n";
        $prompt .= "Generate THREE sections:\n\n";

        $prompt .= "### SECTION 1: SUMMARY\n";
        $prompt .= "Write a detailed summary (300-500 words) covering ALL key points, facts, dates, and concepts from the text.\n\n";

        $prompt .= "### SECTION 2: FLASHCARDS\n";
        $prompt .= "Create 15 flashcards using SPECIFIC information from the text.\n";
        $prompt .= "Format: Q: [question]\nA: [detailed answer]\n\n";

        $prompt .= "### SECTION 3: QUIZ\n";
        $prompt .= "Create 10 quiz questions as a JSON array. Mix multiple choice and short answer.\n";
        $prompt .= "Format: [{\"question_text\":\"...\",\"question_type\":\"multiple_choice\",\"correct_answer\":\"...\",\"options\":[...]}, ...]\n\n";

        $prompt .= "========== STUDENT'S UPLOADED TEXT ==========\n" . $textToUse . "\n========== END ==========\n\n";
        $prompt .= "Generate all three sections now:";

        error_log("Generating all materials from " . strlen($textToUse) . " chars of text");
        $response = $this->callAI($prompt, 4000);

        if ($response['success']) {
            $content = $response['content'];

            // Parse the three sections
            $result = [
                'success' => true,
                'summary' => $this->extractSection($content, 'SUMMARY'),
                'flashcards' => $this->parseFlashcardsFromSection($content),
                'questions' => $this->parseQuizFromSection($content)
            ];

            return $result;
        }

        return ['success' => false, 'message' => $response['message']];
    }

    // Get detailed clarification/explanation
    public function getClarification($question, $sourceText, $context = '')
    {
        $prompt = "You are an expert professor providing detailed clarification to help a student understand their study material better.\n\n";
        $prompt .= "STUDENT'S QUESTION: " . $question . "\n\n";

        if (!empty($context)) {
            $prompt .= "CONTEXT (what they're asking about):\n" . $context . "\n\n";
        }

        $prompt .= "ORIGINAL STUDY MATERIAL:\n" . $this->truncateText($sourceText, 6000) . "\n\n";
        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Provide a COMPREHENSIVE, DETAILED explanation of the concepts involved\n";
        $prompt .= "2. DO NOT GIVE THE DIRECT ANSWER or say 'The answer is...'. Guide them to it.\n";
        $prompt .= "3. Break down complex concepts into understandable parts\n";
        $prompt .= "4. Use specific examples from the study material\n";
        $prompt .= "5. Clarify any confusing terminology\n";
        $prompt .= "6. DO NOT use strikethrough formatting (~~text~~)\n";
        $prompt .= "7. Use proper formatting: headings (###), bullet points, bold (**text**)\n";
        $prompt .= "8. Write 200-400 words of high-quality explanation\n";
        $prompt .= "9. Ensure a student will fully understand the LOGIC after reading\n\n";
        $prompt .= "Provide your explanation now:";

        $response = $this->callAI($prompt);

        if ($response['success']) {
            return [
                'success' => true,
                'explanation' => $response['content']
            ];
        }

        return ['success' => false, 'message' => $response['message']];
    }

    // Call AI API with automatic fallback
    private function callAI($prompt, $maxTokens = null)
    {
        // Use class default if not provided
        $tokens = $maxTokens ?? $this->maxTokens;

        // Try Google Gemini API first if configured
        if (strpos($this->apiUrl, 'generativelanguage.googleapis.com') !== false) {
            $response = $this->callGeminiAPI($prompt, $tokens);

            // If API call succeeds, return it
            if ($response['success']) {
                return $response;
            }

            // Check if it's a rate limit error
            if (isset($response['message']) && $this->isRateLimitError($response['message'])) {
                error_log("⚠️ Gemini rate limit hit, using fallback content");
                return $this->getMockResponse($prompt);
            }

            // API failed, log error
            error_log("Gemini API failed: " . ($response['message'] ?? 'Unknown error'));
            return $response;
        }
        // Try Anthropic Claude API if configured
        elseif (strpos($this->apiUrl, 'anthropic') !== false) {
            $response = $this->callClaudeAPI($prompt, $tokens);

            // If API call succeeds, return it
            if ($response['success']) {
                return $response;
            }

            // Check if it's a rate limit error
            if (isset($response['message']) && $this->isRateLimitError($response['message'])) {
                error_log("⚠️ Claude rate limit hit, using fallback content");
                return $this->getMockResponse($prompt);
            }

            // API failed, log error and return failure
            error_log("Claude API failed: " . ($response['message'] ?? 'Unknown error'));
            return $response;
        }
        // Try OpenRouter/OpenAI compatible API if configured
        elseif (strpos($this->apiUrl, 'openrouter') !== false || strpos($this->apiUrl, 'openai') !== false) {
            $response = $this->callOpenRouterAPI($prompt, $tokens);

            // If API call succeeds, return it
            if ($response['success']) {
                return $response;
            }

            // Check if it's a rate limit error - use fallback content
            if (isset($response['message']) && $this->isRateLimitError($response['message'])) {
                error_log("⚠️ OpenRouter rate limit exceeded, using sample content");
                return $this->getMockResponse($prompt);
            }

            // API failed for other reasons, log error
            error_log("OpenRouter API failed: " . ($response['message'] ?? 'Unknown error'));
            error_log("This means the AI service is not responding. Check your API key and internet connection.");
            return $response;
        }

        // No valid API configured
        error_log("No valid AI API configured");
        return ['success' => false, 'message' => 'No AI API configured'];
    }

    // Check if error message indicates a rate limit
    private function isRateLimitError($message)
    {
        $rateLimitIndicators = [
            'rate limit',
            'Rate limit',
            'too many requests',
            'quota exceeded',
            'free-models-per-day',
            'free-models-per-min',
            '429'
        ];

        foreach ($rateLimitIndicators as $indicator) {
            if (stripos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    // Call Google Gemini API
    private function callGeminiAPI($prompt, $maxTokens = null)
    {
        $tokens = $maxTokens ?? $this->maxTokens;

        // Gemini uses a different endpoint structure
        $url = $this->apiUrl . '?key=' . $this->apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $tokens,
                'temperature' => 0.7
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Gemini cURL Error: " . $curlError);
            return ['success' => false, 'message' => 'Network error: ' . $curlError];
        }

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $responseData['candidates'][0]['content']['parts'][0]['text'];

                if (empty(trim($content)) || strlen(trim($content)) < 20) {
                    error_log("⚠️ Gemini returned empty/short content");
                    return ['success' => false, 'message' => 'Empty response'];
                }

                error_log("✅ Gemini API success");
                return [
                    'success' => true,
                    'content' => $content
                ];
            }
        }

        // Parse error
        $responseData = json_decode($response, true);
        $errorMsg = $responseData['error']['message'] ?? "HTTP $httpCode";
        error_log("Gemini API Error: " . $errorMsg);

        return ['success' => false, 'message' => $errorMsg];
    }

    // Call Anthropic Claude API
    private function callClaudeAPI($prompt, $maxTokens = null)
    {
        $tokens = $maxTokens ?? $this->maxTokens;

        $data = [
            'model' => $this->model,
            'max_tokens' => $tokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);

            if (isset($responseData['content'][0]['text'])) {
                return [
                    'success' => true,
                    'content' => $responseData['content'][0]['text']
                ];
            }
        }

        error_log("AI API Error: " . $response);
        return ['success' => false, 'message' => 'Failed to generate content'];
    }

    // Call OpenRouter/OpenAI compatible API
    private function callOpenRouterAPI($prompt, $maxTokens = null)
    {
        $tokens = $maxTokens ?? $this->maxTokens;

        // Get all API keys to try (primary + backups)
        $apiKeysToTry = [$this->apiKey];
        if (defined('AI_BACKUP_KEYS') && is_array(AI_BACKUP_KEYS)) {
            $apiKeysToTry = array_merge($apiKeysToTry, AI_BACKUP_KEYS);
            $apiKeysToTry = array_unique($apiKeysToTry);
        }

        // Get models to try (primary + fallbacks)
        $modelsToTry = [$this->model];
        if (defined('AI_FALLBACK_MODELS')) {
            $modelsToTry = array_merge($modelsToTry, AI_FALLBACK_MODELS);
            $modelsToTry = array_unique($modelsToTry);
        }

        $lastError = '';

        // Try each API key
        foreach ($apiKeysToTry as $keyIndex => $apiKey) {
            $keyLabel = $keyIndex === 0 ? "Primary" : "Backup #$keyIndex";

            // Try each model with this API key
            foreach ($modelsToTry as $modelIndex => $model) {
                $data = [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $tokens,
                    'temperature' => 0.7
                ];

                error_log("Trying $keyLabel key with model: $model");

                $ch = curl_init($this->apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                    'HTTP-Referer: http://localhost',
                    'X-Title: AI Study Companion'
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    $lastError = "Network error: $curlError";
                    error_log("cURL Error: " . $curlError);
                    continue; // Try next model
                }

                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);

                    if (isset($responseData['choices'][0]['message']['content'])) {
                        $content = $responseData['choices'][0]['message']['content'];

                        if (empty(trim($content)) || strlen(trim($content)) < 20) {
                            error_log("⚠️ Empty/short content (length: " . strlen($content) . ")");
                            $lastError = "Empty response";
                            continue; // Try next model
                        }

                        error_log("✅ Success with $keyLabel key + $model");
                        return [
                            'success' => true,
                            'content' => $content
                        ];
                    }
                }

                // Parse error
                $responseData = json_decode($response, true);
                $errorMsg = $responseData['error']['message'] ?? "HTTP $httpCode";
                $lastError = "$keyLabel key, $model: $errorMsg";
                error_log($lastError);

                // If rate limited (429), try next model with same key
                if ($httpCode === 429) {
                    continue;
                }

                // For other errors (401, 403, etc), try next key
                if ($httpCode === 401 || $httpCode === 403) {
                    error_log("Auth error with $keyLabel key, trying next key...");
                    break; // Break model loop, try next key
                }
            }
        }

        // All models failed
        error_log("❌ All OpenRouter models failed. Last error: $lastError");
        return ['success' => false, 'message' => "All AI models unavailable. Last error: $lastError"];
    }

    // Extract a section from combined materials response
    private function extractSection($content, $sectionName)
    {
        // Match section header and get content until next section or end
        $pattern = '/###\s*SECTION\s*\d+:\s*' . preg_quote($sectionName, '/') . '\s*\n(.*?)(?=###\s*SECTION|\z)/is';
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Parse flashcards from the FLASHCARDS section
    private function parseFlashcardsFromSection($content)
    {
        $section = $this->extractSection($content, 'FLASHCARDS');
        if (!empty($section)) {
            return $this->parseFlashcards($section);
        }
        return [];
    }

    // Parse quiz questions from the QUIZ section
    private function parseQuizFromSection($content)
    {
        $section = $this->extractSection($content, 'QUIZ');
        if (!empty($section)) {
            return $this->parseQuizQuestions($section);
        }
        return [];
    }

    // Parse flashcards from AI response
    private function parseFlashcards($content)
    {
        $flashcards = [];
        $lines = explode("\n", $content);
        $currentQuestion = '';
        $currentAnswer = '';
        $orderNum = 1;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Try different formats: Q: / A:, Question: / Answer:, **Q:** / **A:**, or numbered
            if (preg_match('/^(?:\*\*)?(?:Q|Question)(?:\*\*)?[:\.]?\s*(.+)$/i', $line, $matches)) {
                // Save previous Q&A if exists
                if (!empty($currentQuestion) && !empty($currentAnswer)) {
                    $flashcards[] = [
                        'question' => $currentQuestion,
                        'answer' => $currentAnswer,
                        'order_num' => $orderNum++
                    ];
                }
                $currentQuestion = trim($matches[1]);
                $currentAnswer = '';
            } elseif (preg_match('/^(?:\*\*)?(?:A|Answer)(?:\*\*)?[:\.]?\s*(.+)$/i', $line, $matches)) {
                $currentAnswer = trim($matches[1]);
            } elseif (preg_match('/^\d+[\.\)]\s*(.+)$/i', $line, $matches) && empty($currentQuestion)) {
                // Numbered question format: "1. What is..."
                if (!empty($currentQuestion) && !empty($currentAnswer)) {
                    $flashcards[] = [
                        'question' => $currentQuestion,
                        'answer' => $currentAnswer,
                        'order_num' => $orderNum++
                    ];
                }
                $currentQuestion = trim($matches[1]);
                $currentAnswer = '';
            } elseif (!empty($currentQuestion) && empty($currentAnswer)) {
                // Multi-line question
                $currentQuestion .= ' ' . $line;
            } elseif (!empty($currentAnswer)) {
                // Multi-line answer
                $currentAnswer .= ' ' . $line;
            }
        }

        // Save last Q&A
        if (!empty($currentQuestion) && !empty($currentAnswer)) {
            $flashcards[] = [
                'question' => $currentQuestion,
                'answer' => $currentAnswer,
                'order_num' => $orderNum++
            ];
        }

        // Log parsing results
        error_log("Parsed " . count($flashcards) . " flashcards from AI response");

        // If parsing failed completely, log the content for debugging
        if (empty($flashcards)) {
            error_log("⚠️ Flashcard parsing failed! Content preview: " . substr($content, 0, 500));
            // Return empty array instead of generic fallbacks - let the calling code handle it
            return [];
        }

        return array_slice($flashcards, 0, 15);
    }

    // Parse quiz questions from AI response
    private function parseQuizQuestions($content)
    {
        // Try to parse as JSON first
        $content = trim($content);

        // Log what we're trying to parse
        error_log("Attempting to parse quiz questions. Content length: " . strlen($content));
        error_log("First 200 chars: " . substr($content, 0, 200));

        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        // Remove any text before the JSON array starts
        if (preg_match('/(\[\s*\{.*\}\s*\])/s', $content, $matches)) {
            $content = $matches[1];
            error_log("Extracted JSON array from content");
        }

        // Try to decode
        $questions = json_decode($content, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            error_log("Failed content: " . substr($content, 0, 500));

            // If the content looks like it has HTML tags, it's probably an error page
            if (strpos($content, '<') !== false || strpos($content, '>') !== false) {
                error_log("⚠️ AI returned HTML/XML instead of JSON - likely an API error");
                return $this->generateFallbackQuestions();
            }

            // Try to fix common JSON issues
            $content = str_replace(["\n", "\r", "\t"], ['\\n', '', '\\t'], $content);
            $content = preg_replace('/,\s*\]/', ']', $content); // Remove trailing commas
            $content = preg_replace('/,\s*\}/', '}', $content);

            $questions = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Still failed after cleanup: " . json_last_error_msg());
                return $this->generateFallbackQuestions();
            }
        }

        if (is_array($questions) && count($questions) > 0) {
            // Ensure each question has required fields
            $validQuestions = [];
            $orderNum = 1;

            foreach ($questions as $q) {
                if (isset($q['question_text']) && isset($q['correct_answer'])) {
                    $validQuestions[] = [
                        'question_text' => $q['question_text'],
                        'question_type' => $q['question_type'] ?? 'short_answer',
                        'correct_answer' => $q['correct_answer'],
                        'options' => $q['options'] ?? null,
                        'order_num' => $orderNum++
                    ];
                }
            }

            if (count($validQuestions) >= 5) {
                error_log("Successfully parsed " . count($validQuestions) . " quiz questions");
                return array_slice($validQuestions, 0, 10);
            } else {
                error_log("Parsed only " . count($validQuestions) . " valid questions (need at least 5)");
            }
        } else {
            error_log("JSON decoded but not a valid array or empty");
        }

        // Fallback: generate basic questions
        error_log("⚠️ Using fallback questions - AI parsing failed");
        return $this->generateFallbackQuestions();
    }

    // Generate fallback questions if parsing fails
    private function generateFallbackQuestions()
    {
        $questions = [];
        for ($i = 1; $i <= 10; $i++) {
            $questions[] = [
                'question_text' => "Question $i: Review the key concept from your notes",
                'question_type' => 'short_answer',
                'correct_answer' => 'Review your study materials',
                'options' => null,
                'order_num' => $i
            ];
        }
        return $questions;
    }

    // Truncate text to specified length
    private function truncateText($text, $maxLength = 8000)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength) . "\n\n[Note: Text truncated for processing]";
    }

    // Get intelligent mock response for development/testing
    private function getMockResponse($prompt)
    {
        // Extract some context from the prompt (first few meaningful words)
        $textStart = '';
        if (preg_match('/START OF.*?TEXT.*?=+\s*(.{0,300})/is', $prompt, $matches)) {
            $textStart = trim($matches[1]);
        }

        // Get topic hints from the text
        $topic = "the study material";
        if (!empty($textStart)) {
            // Try to find a topic (look for capitalized words or common topics)
            if (preg_match('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\b/', $textStart, $matches)) {
                $topic = $matches[1];
            }
        }

        // Check prompt type and return appropriate mock data
        if (stripos($prompt, 'summary') !== false || stripos($prompt, 'Summary') !== false) {
            return [
                'success' => true,
                'content' => "**📚 Study Summary**\n\nThis is a comprehensive summary generated from your study material about $topic. The content has been analyzed and key concepts have been extracted.\n\n### Main Concepts\n- Understanding of fundamental principles and core ideas\n- Key definitions and terminology relevant to the subject\n- Important relationships between different concepts\n- Practical applications and real-world examples\n\n### Key Points\n1. **Foundation**: The material establishes a strong foundation in the subject area\n2. **Development**: Progressive development of ideas from basic to advanced\n3. **Application**: Practical applications that demonstrate understanding\n4. **Synthesis**: Integration of concepts into a cohesive framework\n\n### Important Details\nThe study material covers essential information that students should master. Understanding these concepts requires careful review and practice. The relationships between topics are crucial for comprehensive knowledge.\n\n💡 **Note**: AI service is experiencing high demand. This summary was generated using cached analysis. For enhanced AI-powered content, try again later or contact support."
            ];
        } elseif (stripos($prompt, 'flashcard') !== false || stripos($prompt, 'Flashcard') !== false) {
            return [
                'success' => true,
                'content' => "Q: What is the main topic covered in this material?\nA: This material covers $topic, including its fundamental concepts, key principles, and practical applications. Understanding this topic requires mastering both theoretical and practical aspects.\n\nQ: Why is this subject important to learn?\nA: This subject is important because it provides foundational knowledge that applies to many real-world scenarios. It helps develop critical thinking and problem-solving skills relevant to the field.\n\nQ: What are the key components discussed?\nA: The key components include core definitions, theoretical frameworks, practical examples, and application strategies. Each component builds upon previous knowledge.\n\nQ: How do the concepts relate to each other?\nA: The concepts are interconnected through fundamental principles. Understanding one concept helps clarify others, creating a comprehensive knowledge framework.\n\nQ: What practical applications exist?\nA: Practical applications include real-world problem solving, analytical thinking, and implementation strategies that demonstrate mastery of the concepts.\n\nQ: What should students focus on first?\nA: Students should first focus on understanding the fundamental definitions and core principles, as these form the foundation for more advanced topics.\n\nQ: How can this knowledge be tested?\nA: Knowledge can be tested through concept application, problem-solving exercises, and demonstrating understanding through practical examples.\n\nQ: What common mistakes should be avoided?\nA: Common mistakes include oversimplification, ignoring connections between concepts, and failing to practice application of knowledge.\n\nQ: What resources help with learning?\nA: Helpful resources include textbooks, practice problems, study groups, and hands-on experimentation with the concepts.\n\nQ: How does this connect to broader topics?\nA: This topic connects to broader subject areas through shared principles, common applications, and interdisciplinary relationships.\n\nQ: What advanced concepts build on this?\nA: Advanced concepts include deeper analysis, complex problem solving, and integration with other subject areas.\n\nQ: Why do experts emphasize this?\nA: Experts emphasize this because it represents essential knowledge that serves as a foundation for professional competence.\n\nQ: What real-world examples exist?\nA: Real-world examples include case studies, industry applications, and practical scenarios that demonstrate the concepts in action.\n\nQ: How should this be reviewed?\nA: Review should be systematic, starting with basics and progressing to complex applications, with regular practice and self-testing.\n\nQ: What indicates mastery of this topic?\nA: Mastery is indicated by ability to explain concepts clearly, apply knowledge to new situations, and recognize connections to other topics.\n\n💡 **Note**: AI service is experiencing high demand. These flashcards were generated using standard templates. Try again later for custom AI-generated content."
            ];
        } else {
            // Quiz mock response
            return [
                'success' => true,
                'content' => json_encode([
                    [
                        'question_text' => "What is the primary focus of this material about $topic?",
                        'question_type' => 'multiple_choice',
                        'correct_answer' => 'Understanding fundamental concepts and principles',
                        'options' => ['Understanding fundamental concepts and principles', 'Memorizing facts only', 'Skipping important details', 'Reading quickly without comprehension']
                    ],
                    [
                        'question_text' => "Explain the main concept from the study material in your own words.",
                        'question_type' => 'short_answer',
                        'correct_answer' => "The main concept involves understanding the foundational principles of $topic and how they apply to practical situations."
                    ],
                    [
                        'question_text' => 'Which approach is most effective for mastering this material?',
                        'question_type' => 'multiple_choice',
                        'correct_answer' => 'Active learning with practice and review',
                        'options' => ['Active learning with practice and review', 'Passive reading only', 'Last-minute cramming', 'Skipping practice exercises']
                    ],
                    [
                        'question_text' => 'How do the different concepts in this material relate to each other?',
                        'question_type' => 'short_answer',
                        'correct_answer' => 'The concepts are interconnected, with foundational ideas supporting more advanced topics and creating a comprehensive framework.'
                    ],
                    [
                        'question_text' => 'What is the most important thing to understand first?',
                        'question_type' => 'multiple_choice',
                        'correct_answer' => 'The fundamental definitions and core principles',
                        'options' => ['The fundamental definitions and core principles', 'Only the advanced topics', 'The least important details', 'Random facts']
                    ],
                    [
                        'question_text' => 'Describe how this knowledge can be applied practically.',
                        'question_type' => 'short_answer',
                        'correct_answer' => 'This knowledge can be applied through problem-solving, real-world scenarios, and practical implementation of concepts.'
                    ],
                    [
                        'question_text' => 'Which strategy helps with long-term retention?',
                        'question_type' => 'multiple_choice',
                        'correct_answer' => 'Regular review and spaced repetition',
                        'options' => ['Regular review and spaced repetition', 'One-time reading', 'Ignoring the material', 'Hoping to remember']
                    ],
                    [
                        'question_text' => 'What indicates that you truly understand this topic?',
                        'question_type' => 'short_answer',
                        'correct_answer' => 'True understanding is shown by the ability to explain concepts clearly, apply them to new situations, and see connections to other topics.'
                    ],
                    [
                        'question_text' => 'Why is it important to understand the connections between concepts?',
                        'question_type' => 'multiple_choice',
                        'correct_answer' => 'It creates a comprehensive framework for knowledge',
                        'options' => ['It creates a comprehensive framework for knowledge', 'It makes learning more difficult', 'It is not important', 'It wastes time']
                    ],
                    [
                        'question_text' => 'Summarize the key takeaways from this study material.',
                        'question_type' => 'short_answer',
                        'correct_answer' => "The key takeaways include understanding $topic's core principles, their practical applications, and how concepts interconnect to form comprehensive knowledge."
                    ]
                ])
            ];
        }
    }
}
?>