<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$apiKey = getSetting('gemini_api_key');

if (empty($apiKey)) {
    echo json_encode(['status' => 'error', 'message' => 'API klíč není nastaven.']);
    exit;
}

$model = getSetting('gemini_model', 'gemini-2.5-flash-lite');
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Respond with exactly one word: OK"]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['status' => 'error', 'message' => 'Chyba připojení: ' . $error]);
} elseif ($httpCode !== 200) {
    $resData = json_decode($response, true);
    $msg = $resData['error']['message'] ?? 'Neznámá chyba API (Kód: ' . $httpCode . ')';
    echo json_encode(['status' => 'error', 'message' => 'API vrátilo chybu: ' . $msg]);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Připojení k Gemini AI bylo úspěšné!']);
}
