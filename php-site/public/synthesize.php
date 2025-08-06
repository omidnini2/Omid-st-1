<?php
// No authentication / DB. Use constants from config.php or config.sample.php
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
} else {
    require_once __DIR__ . '/../includes/config.sample.php';
}
// Validate that required constants are set
if (AZURE_KEY === 'YOUR_SUBSCRIPTION_KEY') {
    http_response_code(500);
    exit('Please set AZURE_KEY in config.php');
}
define('AZURE_ENDPOINT', 'https://' . AZURE_REGION . '.tts.speech.microsoft.com/cognitiveservices/v1');

header('Access-Control-Allow-Origin: *'); // simple CORS
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');
if (!$text) {
    http_response_code(400);
    exit('Missing text');
}

$key = AZURE_KEY;
$region = AZURE_REGION;
$voice = AZURE_VOICE;

$endpoint = AZURE_ENDPOINT;
$ssml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><speak version=\"1.0\" xml:lang=\"en-US\"><voice name=\"{$voice}\">" . htmlspecialchars($text) . "</voice></speak>";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/ssml+xml',
        'X-Microsoft-OutputFormat: audio-16khz-32kbitrate-mono-mp3',
        'Ocp-Apim-Subscription-Key: ' . $key,
    ],
    CURLOPT_POSTFIELDS => $ssml,
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode !== 200 || $response === false) {
    http_response_code(500);
    echo 'Azure TTS error';
    error_log('Azure error: ' . curl_error($ch) . ' / Response: ' . $response);
    curl_close($ch);
    exit;
}
curl_close($ch);
header('Content-Type: audio/mpeg');
header('Content-Disposition: inline; filename="speech.mp3"');
header('Content-Length: ' . strlen($response));
echo $response;
?>