<?php
require_once '../includes/functions.php';
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

if (!function_exists('imap_open')) {
    echo json_encode(['status' => 'error', 'message' => 'PHP rozšíření IMAP není nainstalováno na tomto serveru.']);
    exit;
}

$ssl = $imap_encryption === 'ssl' ? '/ssl' : ($imap_encryption === 'tls' ? '/tls' : '/notls');
$mailbox = "{" . $imap_server . ":" . $imap_port . "/imap" . $ssl . "}INBOX";

$mbox = @imap_open($mailbox, $imap_user, $imap_password);

if (!$mbox) {
    echo json_encode(['status' => 'error', 'message' => 'Nelze se připojit k IMAP: ' . imap_last_error()]);
    exit;
}

// Search for UNSEEN messages
$emails = imap_search($mbox, 'UNSEEN');

$importedCount = 0;
if ($emails) {
    arsort($emails); // Newest first
    
    foreach ($emails as $mail_id) {
        $overview = imap_fetch_overview($mbox, $mail_id, 0);
        $overview = $overview[0];
        
        $structure = imap_fetchstructure($mbox, $mail_id);
        $body = "";
        
        // Simple body fetch (trying plaintext first)
        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart
            foreach ($structure->parts as $part_number => $part) {
                if ($part->subtype == 'PLAIN') {
                    $body = imap_fetchbody($mbox, $mail_id, $part_number + 1);
                    break;
                }
            }
            if (empty($body)) {
                $body = imap_fetchbody($mbox, $mail_id, 1);
            }
        } else {
            // Single part
            $body = imap_body($mbox, $mail_id);
        }
        
        // Decode body if needed
        if ($structure->encoding == 3) $body = base64_decode($body);
        elseif ($structure->encoding == 4) $body = quoted_printable_decode($body);

        $from = $overview->from;
        // Parse email from "Name <email@domain.com>"
        if (preg_match('/<(.*?)>/', $from, $matches)) {
            $from_email = $matches[1];
        } else {
            $from_email = $from;
        }

        $subject = isset($overview->subject) ? imap_utf8($overview->subject) : '(Bez předmětu)';
        $uid = $overview->uid;

        if (processInboxMail($uid, $from_email, $subject, $body)) {
            $importedCount++;
            // Optionally mark as read or delete
            // imap_setflag_full($mbox, $uid, "\\Seen", ST_UID);
        }
    }
}

imap_close($mbox);

echo json_encode([
    'status' => 'success',
    'message' => "Synchronizace dokončena. Importováno $importedCount nových položek.",
    'count' => $importedCount
]);
