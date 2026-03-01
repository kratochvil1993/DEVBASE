<?php
require_once 'includes/functions.php';
$apiKey = getSetting('gemini_api_key');
if (!$apiKey) die('No API key');

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
echo "MODELS FOUND:\n";
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        echo $m['name'] . " (" . $m['displayName'] . ")\n";
    }
} else {
    print_r($data);
}
