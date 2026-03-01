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
} elseif ($action === 'minify_code') {
    $prompt = "Jsi expertní programátor. Vezmi následující kód a odstraň z něj všechny zbytečné bílé znaky, nové řádky a komentáře tak, aby byl co nejmenší (minifikovaný), ale stále funkční. Vrat POUZE výsledný minifikovaný kód bez jakéhokoliv vysvětlování, uvozovek nebo Markdown formátování.\n\nZde je kód:\n\n" . $content;
} elseif ($action === 'beautify_code') {
    $prompt = "Jsi expertní programátor. Vezmi následující kód a přehledně ho zformátuj. Použij standardní odsazení (4 mezery), přidej nové řádky tam, kde chybí pro lepší čitelnost. Zachovej funkčnost. Vrat POUZE výsledný zformátovaný kód bez jakéhokoliv vysvětlování, uvozovek nebo Markdown formátování.\n\nZde je kód:\n\n" . $content;
} elseif ($action === 'refactor_code') {
    $prompt = "Jsi špičkový softwarový architekt. Tvým úkolem je provést refaktorování následujícího kódu tak, aby byl čistší, čitelnější a efektivnější. Zachovej funkčnost. Popiš stručně provedené změny v bodech (začni '*') a poté uveď kompletní refaktorovaný kód v bloku označeném jako 'KÓD:\n\n'. Odpověz v češtině.\n\nZde je kód k refaktorování:\n\n" . $content;
} elseif ($action === 'debug_code') {
    $prompt = "Jsi expertní vývojář a debugger. Analyzuj následující kód a najdi v něm chyby (syntaktické, logické nebo bezpečnostní). Stručně vysvětli, co je špatně, a navrhni opravu. Pokud je to možné, uveď opravenou část kódu v bloku 'OPRAVA:\n\n'. Odpověz v češtině.\n\nZde je kód k analýze:\n\n" . $content;
} elseif ($action === 'summarize_note') {
    $prompt = "Jsi expertní analytik. Tvým úkolem je analyzovat následující text a vytvořit z něj přehledný výstup. Nejdříve napiš krátce (1-2 věty), o čem celá poznámka v základu je. Poté v bodech vypiš, co se v textu obecně řešilo a co je nejdůležitější (hlavní priority/sdělení). Nakonec vypiš konkrétní fakta, úkoly nebo termíny rozdělené do logických bloků. Každý bod v seznamu MUSÍ začínat znakem '*' na novém řádku. Vyber jen to podstatné a zachovej přehlednost. Odpověz v češtině.\n\nZde je text k analýze:\n\n" . $content;
} elseif ($action === 'structure_note') {
    $prompt = "Jsi expertní editor. Tvým úkolem je vzít následující neuspořádaný 'brain dump' (útržky myšlenek, odrážky, poznámky) a přetvořit jej do profesionálně vypadající, strukturované poznámky. Používej nadpisy (Markdown # nebo ##), odrážky a logické odstavce. Zachovej všechny důležité informace, ale dej jim řád a srozumitelnost. Odpověz v češtině. Zde je obsah k přepracování:\n\n" . $content;
} elseif ($action === 'extract_todos') {
    $prompt = "Jsi expertní asistent. Tvým úkolem je v následujícím textu identifikovat všechny úkoly, povinnosti a termíny. Vypiš je jako jednoduchý seznam, kde každý úkol je na novém řádku a začíná [TODO]. Pokud u úkolu identifikuješ i termín (datum), uveď ho ve formátu (YYYY-MM-DD) na konci řádku. Pokud v textu žádné úkoly nejsou, napiš 'Žádné úkoly nebyly nalezeny.'. Odpověz v češtině. Zde je text:\n\n" . $content;
} elseif ($action === 'generate_tldr') {
    $prompt = "Jsi expertní editor. Vytvoř velmi krátké (max. 2-3 věty) shrnutí (TL;DR) následujícího textu. Zaměř se jen na to nejpodstatnější. Odpověz v češtině. Zde je text:\n\n" . $content;
} elseif ($action === 'format_note') {
    $prompt = "Jsi expertní editor. Tvým úkolem je vzít následující text a zformátovat ho tak, aby byl maximálně přehledný, profesionální a čitelný.
    1. Použij vhodné nadpisy (Markdown # nebo ##).
    2. Použij odrážky tam, kde to dává smysl.
    3. Důležité pojmy nebo klíčové informace zvýrazni tučně (**bold**).
    4. Oprav základní gramatické chyby a interpunkci.
    5. Zachovej VŠECHNY informace, ale dej jim řád.
    6. Odpověz POUZE výsledným zformátovaným textem v češtině.
    
    Zde je text k formátování:\n\n" . $content;
} elseif ($action === 'custom_prompt') {
    $userPrompt = $data['prompt'] ?? '';
    $prompt = "Jsi expertní programátor. Na základě následujícího kódu a instrukce od uživatele proveď požadovanou akci. Pokud uživatel chce kód upravit, uveď nejdříve stručné vysvětlení změn a poté celý výsledný kód v bloku označeném jako 'KÓD:\n\n'. Pokud chce něco vysvětlit nebo analyzovat, odpověz jasně a stručně v češtině.
    
    Instrukce od uživatele: " . $userPrompt . "
    
    Zde je kód k práci:\n\n" . $content;
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
        "max_tokens" => 4000
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
            "maxOutputTokens" => 4000,
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
