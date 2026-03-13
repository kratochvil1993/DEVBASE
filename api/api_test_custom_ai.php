<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$url = getSetting('custom_ai_endpoint');
$model = getSetting('custom_ai_model', 'local-model');
$apiKey = getSetting('custom_ai_api_key', '');

if (empty($url)) {
    echo json_encode(['status' => 'error', 'message' => 'Endpoint URL pro vlastní AI není nastavena.']);
    exit;
}

$data = [
    "model" => $model,
    "messages" => [
        [
            "role" => "user",
            "content" => "Respond with exactly one word: OK"
        ]
    ],
    "max_tokens" => 10
];

$headers = ['Content-Type: application/json'];
if (!empty($apiKey)) {
    $headers[] = 'Authorization: Bearer ' . $apiKey;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['status' => 'error', 'message' => 'Chyba připojení k lokální AI: ' . $error . '. Ujistěte se, že váš lokální server (např. Ollama) běží a je přístupný.']);
} elseif ($httpCode !== 200) {
    $resData = json_decode($response, true);
    $msg = $resData['error']['message'] ?? 'Neznámá chyba lokálního API (HTTP Kód: ' . $httpCode . ')';
    echo json_encode(['status' => 'error', 'message' => 'Lokální API vrátilo chybu: ' . $msg]);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Připojení k vaší vlastní AI bylo úspěšné! (HTTP 200)']);
}
