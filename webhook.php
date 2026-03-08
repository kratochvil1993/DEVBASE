<?php

// ============================================================
//  GitHub Webhook Handler
//  URL: https://tvojserver.cz/webhook.php
// ============================================================

// --- KONFIGURACE -------------------------------------------

// Stejný secret, který nastavíš v GitHubu (Settings → Webhooks → Secret)
define('WEBHOOK_SECRET', 'PlzenJeNejlepsiMesto123');

// Absolutní cesta k projektu na serveru (kde se spustí git pull)
define('PROJECT_PATH', dirname(__FILE__));

// Branch, na kterou reagujeme (null = reaguj na všechny)
define('WATCH_BRANCH', 'main');

// Soubor pro logování
define('LOG_FILE', __DIR__ . '/webhook_log.txt');

// Příkaz, který se spustí po git pull (prázdný string = nic dalšího nespouštěj)
// Příklad: 'composer install --no-dev --no-interaction 2>&1'
define('POST_DEPLOY_CMD', '');

// -----------------------------------------------------------


// Pouze POST requesty
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Přečti raw tělo requestu
$payload_raw = file_get_contents('php://input');
if (empty($payload_raw)) {
    http_response_code(400);
    die('Empty payload');
}

// ---- Ověření podpisu (HMAC SHA-256) -----------------------
$signature_header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($signature_header)) {
    log_event('ERROR', 'Chybí X-Hub-Signature-256 hlavička');
    http_response_code(401);
    die('Unauthorized – missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload_raw, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature_header)) {
    log_event('ERROR', 'Neplatný podpis – pravděpodobně špatný secret nebo cizí request');
    http_response_code(403);
    die('Forbidden – invalid signature');
}

// ---- Parsování payloadu -----------------------------------
$payload = json_decode($payload_raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    log_event('ERROR', 'Nelze parsovat JSON payload');
    http_response_code(400);
    die('Invalid JSON');
}

// GitHub event typ
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';

// Reagujeme jen na push eventy
if ($event !== 'push') {
    log_event('INFO', "Event '$event' ignorován – reagujeme pouze na push");
    http_response_code(200);
    die("OK – event '$event' ignored");
}

// Zjisti branch
$ref    = $payload['ref'] ?? '';
$branch = str_replace('refs/heads/', '', $ref);

// Kontrola branch
if (WATCH_BRANCH !== null && $branch !== WATCH_BRANCH) {
    log_event('INFO', "Push do branch '$branch' ignorován (sledujeme jen '" . WATCH_BRANCH . "')");
    http_response_code(200);
    die("OK – branch '$branch' ignored");
}

// Info o commitu
$pusher  = $payload['pusher']['name']      ?? 'neznámý';
$commits = count($payload['commits']       ?? []);
$head    = $payload['head_commit']['id']   ?? 'N/A';
$message = $payload['head_commit']['message'] ?? 'N/A';

log_event('INFO', "Push od '$pusher', branch: '$branch', commits: $commits, HEAD: " . substr($head, 0, 8));
log_event('INFO', "Commit message: " . trim($message));


// ---- Git pull ---------------------------------------------
$cmd    = "cd " . escapeshellarg(PROJECT_PATH) . " && git pull origin " . escapeshellarg($branch) . " 2>&1";
$output = shell_exec($cmd);

log_event('GIT', "Výstup git pull:\n" . trim($output));


// ---- Volitelný post-deploy příkaz -------------------------
if (!empty(POST_DEPLOY_CMD)) {
    $post_cmd    = "cd " . escapeshellarg(PROJECT_PATH) . " && " . POST_DEPLOY_CMD;
    $post_output = shell_exec($post_cmd);
    log_event('POST-DEPLOY', trim($post_output));
}


// ---- Odpověď GitHubu --------------------------------------
http_response_code(200);
echo json_encode([
    'status'  => 'ok',
    'branch'  => $branch,
    'pusher'  => $pusher,
    'commits' => $commits,
    'head'    => substr($head, 0, 8),
]);


// ===========================================================
//  Helper: logování
// ===========================================================
function log_event(string $level, string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
