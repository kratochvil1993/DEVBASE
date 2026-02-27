<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$provider = getSetting('ai_provider', 'gemini');
$apiKey = ($provider === 'openai') ? getSetting('openai_api_key') : getSetting('gemini_api_key');

if (empty($apiKey)) {
    echo json_encode(['status' => 'error', 'message' => 'AI není nakonfigurováno. Vložte API klíč pro ' . ($provider === 'openai' ? 'OpenAI' : 'Gemini') . ' v nastavení.']);
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
} elseif ($action === 'generate_description') {
    $prompt = "Jsi expertní programátor. Na základě následujícího kódu vygeneruj velmi krátký a výstižný popis (maximálně 10 slov). Odpověz v češtině jako prostý text bez jakéhokoliv formátování, uvozovek nebo odrážek. Zde je kód:\n\n" . $content;
} elseif ($action === 'generate_title') {
    $prompt = "Jsi expertní programátor a copywriter. Na základě následujícího textu (může to být kód nebo textová poznámka) vygeneruj krátký a výstižný název (maximálně 3-5 slov). Odpověz v češtině jako prostý text bez jakéhokoliv formátování, uvozovek nebo odrážek.\n\n" . $content;
} elseif ($action === 'generate_note_title') {
    $prompt = "Jsi expertní editor. Na základě následujícího obsahu poznámky vygeneruj krátký a výstižný název (maximálně 3-5 slov). Odpověz v češtině jako prostý text bez jakéhokoliv formátování, uvozovek nebo odrážek. Zde je obsah:\n\n" . $content;
} elseif ($action === 'grammar_check') {
    $prompt = "Jsi expertní korektor češtiny. Zkontroluj prosím následující text na gramatické a pravopisné chyby. Pokud najdeš chyby, oprav je a vypiš opravený text. Pokud je text v pořádku, napiš 'Text je gramaticky správně.'. Odpověz stručně v češtině. Zde je text:\n\n" . $content;
} elseif ($action === 'refactor_code') {
    $prompt = "Jsi špičkový softwarový architekt. Tvým úkolem je provést refaktorování následujícího kódu tak, aby byl čistší, čitelnější a efektivnější. Zachovej funkčnost. Popiš stručně provedené změny v bodech (začni '*') a poté uveď kompletní refaktorovaný kód v bloku označeném jako 'KÓD:\n\n'. Odpověz v češtině.\n\nZde je kód k refaktorování:\n\n" . $content;
} elseif ($action === 'debug_code') {
    $prompt = "Jsi expertní vývojář a debugger. Analyzuj následující kód a najdi v něm chyby (syntaktické, logické nebo bezpečnostní). Stručně vysvětli, co je špatně, a navrhni opravu. Pokud je to možné, uveď opravenou část kódu v bloku 'OPRAVA:\n\n'. Odpověz v češtině.\n\nZde je kód k analýze:\n\n" . $content;
} elseif ($action === 'summarize_note') {
    $prompt = "Jsi expertní analytik. Tvým úkolem je analyzovat následující text a vytvořit z něj přehledný výstup. Nejdříve napiš krátce (1-2 věty), o čem celá poznámka v základu je. Poté v bodech vypiš, co se v textu obecně řešilo a co je nejdůležitější (hlavní priority/sdělení). Nakonec vypiš konkrétní fakta, úkoly nebo termíny rozdělené do logických bloků. Každý bod v seznamu MUSÍ začínat znakem '*' na novém řádku. Vyber jen to podstatné a zachovej přehlednost. Odpověz v češtině.\n\nZde je text k analýze:\n\n" . $content;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
    exit;
}

if ($provider === 'openai') {
    // OpenAI Implementation
    $model = getSetting('openai_model', 'gpt-4o-mini');
    $url = "https://api.openai.com/v1/chat/completions";
    
    $postData = [
        "model" => $model,
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.7,
        "max_tokens" => 1000
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];
} else {
    // Gemini Implementation
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
    
    $headers = ['Content-Type: application/json'];
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['status' => 'error', 'message' => 'Chyba připojení: ' . $error]);
} elseif ($httpCode !== 200) {
    $resData = json_decode($response, true);
    if ($provider === 'openai') {
        $msg = $resData['error']['message'] ?? 'Chyba OpenAI API (' . $httpCode . ')';
    } else {
        $msg = $resData['error']['message'] ?? 'Chyba Gemini API (' . $httpCode . ')';
    }
    echo json_encode(['status' => 'error', 'message' => $msg]);
} else {
    $resData = json_decode($response, true);
    if ($provider === 'openai') {
        $aiText = $resData['choices'][0]['message']['content'] ?? 'Nepodařilo se získat odpověď od OpenAI.';
    } else {
        $aiText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? 'Nepodařilo se získat odpověď od Gemini.';
    }
    echo json_encode(['status' => 'success', 'answer' => $aiText]);
}
