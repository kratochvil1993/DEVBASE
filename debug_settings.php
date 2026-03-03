<?php
require_once 'includes/functions.php';
header('Content-Type: application/json');

$imap_server = getSetting('imap_server');
$imap_port = getSetting('imap_port', '993');
$imap_user = getSetting('imap_user');
$imap_password = getSetting('imap_password');
$imap_encryption = getSetting('imap_encryption', 'ssl');

$settings = [
    'inbox_enabled' => getSetting('inbox_enabled'),
    'inbox_auto_check' => getSetting('inbox_auto_check'),
    'imap_extension' => function_exists('imap_open'),
    'imap_server' => $imap_server,
    'imap_port' => $imap_port,
    'imap_user' => $imap_user,
    'imap_encryption' => $imap_encryption
];

$ssl = $imap_encryption === 'ssl' ? '/ssl/novalidate-cert' : ($imap_encryption === 'tls' ? '/tls/novalidate-cert' : '/notls');
$mailbox = "{" . $imap_server . ":" . $imap_port . "/imap" . $ssl . "}INBOX";

$mbox = @imap_open($mailbox, $imap_user, $imap_password);
if ($mbox) {
    $settings['imap_status'] = 'Connected';
    $unseen = imap_search($mbox, 'UNSEEN');
    $settings['unseen_count'] = $unseen ? count($unseen) : 0;
    imap_close($mbox);
} else {
    $settings['imap_status'] = 'Error: ' . imap_last_error();
}

echo json_encode($settings, JSON_PRETTY_PRINT);
