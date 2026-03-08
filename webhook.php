<?php

// ============================================================
//  GitHub Webhook Handler
//  URL: https://tvojserver.cz/webhook.php
// ============================================================

// --- KONFIGURACE -------------------------------------------

// Secret je vypnutý – webhook akceptuje requesty bez ověření podpisu

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

// ---- Ověření podpisu je VYPNUTO -------------------------
// Pro zabezpečení na produkci nastav WEBHOOK_SECRET a odkomentuj blok níže:
//
// $signature_header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
// $expected = 'sha256=' . hash_hmac('sha256', $payload_raw, 'TVUJ_SECRET');
// if (!hash_equals($expected, $signature_header)) {
//     http_response_code(403); die('Forbidden – invalid signature');
// }
log_event('INFO', 'Příchozí webhook request (bez ověření podpisu)');

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

// Zkontroluj, zda je shell_exec dostupný
if (!function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
    log_event('ERROR', 'shell_exec() je zakázán na tomto serveru – git pull nelze spustit');
    http_response_code(500);
    die('shell_exec disabled');
}

// HOME musí být nastaveno, aby git našel SSH klíče (~/.ssh)
$home = posix_getpwuid(posix_getuid())['dir'] ?? '/var/www';

$cmd = "HOME=" . escapeshellarg($home)
     . " GIT_SSH_COMMAND='ssh -o StrictHostKeyChecking=no -o BatchMode=yes'"
     . " git -C " . escapeshellarg(PROJECT_PATH)
     . " pull origin " . escapeshellarg($branch)
     . " 2>&1";

log_event('GIT', "Spouštím: git -C " . PROJECT_PATH . " pull origin $branch");

$output = shell_exec($cmd);

if ($output === null) {
    log_event('GIT', "CHYBA: shell_exec vrátil null – příkaz selhal nebo je zakázán");
} elseif (trim($output) === '') {
    log_event('GIT', "git pull proběhl bez výstupu (pravděpodobně already up to date)");
} else {
    log_event('GIT', "Výstup:\n" . trim($output));
}


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
