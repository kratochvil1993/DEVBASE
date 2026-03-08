<?php
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;

checkApiSecurity();

header('Content-Type: application/json');

if (getSetting('inbox_enabled', '0') !== '1') {
    echo json_encode(['status' => 'error', 'message' => 'Inbox není povolen v nastavení.']);
    exit;
}

$imap_server = getSetting('imap_server');
$imap_port = getSetting('imap_port', '993');
$imap_user = getSetting('imap_user');
$imap_password = getSetting('imap_password');
$imap_encryption = getSetting('imap_encryption', 'ssl');

if (empty($imap_server) || empty($imap_user) || empty($imap_password)) {
    echo json_encode(['status' => 'error', 'message' => 'IMAP konfigurace není kompletní.']);
    exit;
}

try {
    $cm = new ClientManager();
    $client = $cm->make([
        'host'          => $imap_server,
        'port'          => $imap_port,
        'encryption'    => $imap_encryption === 'none' ? false : $imap_encryption,
        'validate_cert' => false,
        'username'      => $imap_user,
        'password'      => $imap_password,
        'protocol'      => 'imap'
    ]);

    $client->connect();
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Nelze se připojit k IMAP: ' . $e->getMessage()]);
    exit;
}

$importedCount = 0;

try {
    $folder = $client->getFolder("INBOX");
    $messages = $folder->query()->unseen()->get();
    
    if ($messages->count() > 0) {
        foreach ($messages as $message) {
            $uid = $message->getUid();
            
            // Get from email
            $from_addresses = $message->getFrom();
            $from_email = !empty($from_addresses) ? $from_addresses[0]->mail : '(Neznámý odesílatel)';
            
            // Get subject - already decoded by library
            $subject = $message->getSubject();
            if (empty($subject)) $subject = '(Bez předmětu)';
            
            // Get body - automatically handles encoding and charset
            $body = $message->getTextBody();
            if (empty($body)) {
                $body = $message->getHTMLBody(true); // fall back to HTML stripped
            }
            if (empty($body)) {
                $body = "(E-mail nemá žádný textový obsah)";
            }

            if (processInboxMail($uid, $from_email, $subject, $body)) {
                $importedCount++;
            }
            
            // Mark as seen in the library
            $message->setFlag('Seen');
        }
    }
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Chyba při stahování zpráv: ' . $e->getMessage()]);
    exit;
}

$client->disconnect();

// Prepare data for real-time UI update
$stats = getGlobalStats();
ob_start();
include '../includes/header_notifications.php';
$notifications_html = ob_get_clean();

echo json_encode([
    'status' => 'success',
    'message' => "Synchronizace dokončena. Importováno $importedCount nových položek.",
    'count' => $importedCount,
    'stats' => $stats,
    'nav_notifications_html' => $notifications_html
]);


