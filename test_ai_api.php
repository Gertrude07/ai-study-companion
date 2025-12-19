<?php
/**
 * AI API Test Script
 * Run this directly on the server to diagnose API issues
 * Usage: php test_ai_api.php
 */

echo "=== AI API Diagnostic Test ===\n\n";

// Step 1: Check if config file exists
$configPath = __DIR__ . '/config/ai_config.php';
echo "1. Checking config file...\n";
if (file_exists($configPath)) {
    echo "   ✓ Config file exists: $configPath\n";
    require_once $configPath;
} else {
    echo "   ✗ ERROR: Config file missing!\n";
    echo "   Run: cp config/ai_config.gemini.php config/ai_config.php\n";
    exit(1);
}

// Step 2: Check constants
echo "\n2. Checking configuration constants...\n";
$constants = ['AI_API_KEY', 'AI_API_URL', 'AI_MODEL', 'AI_MAX_TOKENS'];
foreach ($constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        if ($const === 'AI_API_KEY') {
            // Hide most of the key for security
            $display = substr($value, 0, 10) . '...' . substr($value, -4);
        } else {
            $display = $value;
        }
        echo "   $const = $display\n";
    } else {
        echo "   ✗ ERROR: $const is not defined!\n";
    }
}

// Step 3: Check if URL is for Gemini
echo "\n3. Checking API type...\n";
$apiUrl = defined('AI_API_URL') ? AI_API_URL : '';
if (strpos($apiUrl, 'generativelanguage.googleapis.com') !== false) {
    echo "   ✓ Configured for Gemini API\n";
} elseif (strpos($apiUrl, 'openrouter') !== false) {
    echo "   ⚠ Configured for OpenRouter (not Gemini!)\n";
    echo "   This is likely the issue. Update to use Gemini URL.\n";
} else {
    echo "   ? Unknown API: $apiUrl\n";
}

// Step 4: Test the API call
echo "\n4. Testing Gemini API call...\n";
if (!defined('AI_API_KEY') || !defined('AI_API_URL')) {
    echo "   ✗ Cannot test: missing configuration\n";
    exit(1);
}

$testPrompt = "Say 'Hello, the API is working!' in exactly those words.";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $testPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 100,
        'temperature' => 0.7
    ]
];

$url = AI_API_URL . '?key=' . AI_API_KEY;

echo "   Making request to: " . preg_replace('/key=.+/', 'key=***', $url) . "\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "\n5. API Response:\n";
echo "   HTTP Code: $httpCode\n";

if ($curlError) {
    echo "   ✗ cURL Error: $curlError\n";
    exit(1);
}

$result = json_decode($response, true);

if ($httpCode === 200) {
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];
        echo "   ✓ SUCCESS! API Response: $text\n";
        echo "\n=== API is working correctly! ===\n";
    } else {
        echo "   ⚠ Got 200 but unexpected response format\n";
        echo "   Response: " . substr($response, 0, 500) . "\n";
    }
} else {
    echo "   ✗ API Error!\n";
    if (isset($result['error']['message'])) {
        echo "   Error: " . $result['error']['message'] . "\n";

        // Check for common issues
        if (strpos($result['error']['message'], 'API_KEY') !== false) {
            echo "\n   → Your API key is invalid or expired.\n";
            echo "   → Get a new key at: https://aistudio.google.com/app/apikey\n";
        }
        if (strpos($result['error']['message'], 'not found') !== false) {
            echo "\n   → Model not found. Check AI_API_URL has correct model name.\n";
            echo "   → Try: gemini-2.0-flash or gemini-1.5-flash or gemini-pro\n";
        }
        if (
            strpos($result['error']['message'], 'quota') !== false ||
            strpos($result['error']['message'], 'rate') !== false
        ) {
            echo "\n   → Rate limit or quota exceeded. Wait and try again.\n";
        }
    } else {
        echo "   Full response: $response\n";
    }
}

echo "\n=== Test Complete ===\n";
?>