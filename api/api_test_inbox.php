<?php
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;

checkApiSecurity();

header('Content-Type: application/json');

$imap_server = getSetting('imap_server');
$imap_port = getSetting('imap_port', '993');
$imap_user = getSetting('imap_user');
$imap_password = getSetting('imap_password');
$imap_encryption = getSetting('imap_encryption', 'ssl');

if (empty($imap_server) || empty($imap_user) || empty($imap_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Chybějící IMAP konfigurace. Vyplňte prosím server, uživatele a heslo.']);
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
    $folder = $client->getFolder("INBOX");
    $count = $folder->messages()->unseen()->count();

    echo json_encode([
        'status' => 'success',
        'message' => "Spojení s IMAP serverem bylo úspěšné! Ve schránce bylo nalezeno $count nových (nepřečtených) zpráv."
    ]);

    $client->disconnect();
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => "Nepodařilo se připojit k IMAP serveru. Chyba: " . $e->getMessage()]);
}

