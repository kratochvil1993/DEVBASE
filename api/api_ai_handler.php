<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$apiKey = getSetting('gemini_api_key');

if (empty($apiKey)) {
    echo json_encode(['status' => 'error', 'message' => 'AI není nakonfigurováno. Vložte API klíč v nastavení.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$content = $data['content'] ?? '';

if (empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí obsah ke zpracování.']);
    exit;
}

$prompt = "";
if ($action === 'explain_code') {
    $prompt = "Jsi expertní programátor. Vysvětli prosím stručně a výstižně jednou nebo dvěma větami, co dělá následující kód. Odpověz v češtině jako prostý text bez jakéhokoliv formátování nebo odrážek. Zde je kód:\n\n" . $content;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
    exit;
}

$model = getSetting('gemini_model', 'gemini-2.5-flash-lite');
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;

$postData = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 1000,
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['status' => 'error', 'message' => 'Chyba připojení: ' . $error]);
} elseif ($httpCode !== 200) {
    $resData = json_decode($response, true);
    $msg = $resData['error']['message'] ?? 'Chyba API (' . $httpCode . ')';
    echo json_encode(['status' => 'error', 'message' => $msg]);
} else {
    $resData = json_decode($response, true);
    $aiText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? 'Nepodařilo se získat odpověď.';
    echo json_encode(['status' => 'success', 'answer' => $aiText]);
}
