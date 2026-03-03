<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$imap_server = getSetting('imap_server');
$imap_port = getSetting('imap_port', '993');
$imap_user = getSetting('imap_user');
$imap_password = getSetting('imap_password');
$imap_encryption = getSetting('imap_encryption', 'ssl');

// Test IMAP
if (empty($imap_server) || empty($imap_user) || empty($imap_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Chybějící IMAP konfigurace. Vyplňte prosím server, uživatele a heslo.']);
    exit;
}

if (!function_exists('imap_open')) {
    echo json_encode(['status' => 'error', 'message' => 'PHP rozšíření IMAP není na tomto serveru nainstalováno.']);
    exit;
}

// Build mailbox string
$ssl = $imap_encryption === 'ssl' ? '/ssl' : ($imap_encryption === 'tls' ? '/tls' : '/notls');
$mailbox = "{" . $imap_server . ":" . $imap_port . "/imap" . $ssl . "}INBOX";

// Suppress errors during connection attempt
$mbox = @imap_open($mailbox, $imap_user, $imap_password, OP_HALFOPEN);

if ($mbox) {
    try {
        $check = imap_check($mbox);
        $count = $check->Nmsgs;
        echo json_encode([
            'status' => 'success',
            'message' => "Spojení s IMAP serverem bylo úspěšné! Ve schránce bylo nalezeno $count zpráv."
        ]);
        imap_close($mbox);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Chyba při kontrole schránky: " . $e->getMessage()]);
    }
} else {
    $error = imap_last_error();
    echo json_encode(['status' => 'error', 'message' => "Nepodařilo se připojit k IMAP serveru. Chyba: $error"]);
}
