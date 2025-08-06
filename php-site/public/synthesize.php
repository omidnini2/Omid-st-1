<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
header('Access-Control-Allow-Origin: *'); // simple CORS
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Not authenticated');
}

$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');
if (!$text) {
    http_response_code(400);
    exit('Missing text');
}

$db = getDB();
$stmt = $db->prepare('SELECT azure_key, azure_region, azure_voice FROM users WHERE id=:id');
$stmt->execute([':id'=>$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !$user['azure_key'] || !$user['azure_region'] || !$user['azure_voice']) {
    http_response_code(400);
    exit('Credentials not set');
}

$key = $user['azure_key'];
$region = $user['azure_region'];
$voice = $user['azure_voice'];

$endpoint = "https://{$region}.tts.speech.microsoft.com/cognitiveservices/v1";
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