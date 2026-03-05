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

$ssl = $imap_encryption === 'ssl' ? '/ssl/novalidate-cert' : ($imap_encryption === 'tls' ? '/tls/novalidate-cert' : '/notls');
$mailbox = "{" . $imap_server . ":" . $imap_port . "/imap" . $ssl . "}INBOX";

$mbox = @imap_open($mailbox, $imap_user, $imap_password);

if (!$mbox) {
    $error = imap_last_error();

    echo json_encode(['status' => 'error', 'message' => 'Nelze se připojit k IMAP: ' . $error]);
    exit;
}

// Search for UNSEEN messages
$emails = imap_search($mbox, 'UNSEEN');

// Helper function to recursively find the body and handle encoding/charset
function getIMAPBody($mbox, $mail_id, $structure, $part_number = null) {
    // If it's a message/rfc822, we need to dive deeper into the structure
    if ($structure->type == 2) { 
        return getIMAPBody($mbox, $mail_id, $structure->parts[0], $part_number);
    }

    // Found a plain text part or it's a single part message
    if ($structure->type == 0 && ($structure->subtype == 'PLAIN' || empty($structure->parts))) {
        $data = $part_number ? imap_fetchbody($mbox, $mail_id, $part_number) : imap_body($mbox, $mail_id);
        
        // Decode based on encoding
        if ($structure->encoding == 3) $data = base64_decode($data);
        elseif ($structure->encoding == 4) $data = quoted_printable_decode($data);
        
        // Handle charset conversion
        $charset = '';
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtolower($param->attribute) == 'charset') {
                    $charset = $param->value;
                    break;
                }
            }
        }
        
        if ($charset && strtolower($charset) != 'utf-8' && strtolower($charset) != 'us-ascii') {
            if (function_exists('mb_convert_encoding')) {
                $data = @mb_convert_encoding($data, 'UTF-8', $charset);
            } elseif (function_exists('iconv')) {
                $data = @iconv($charset, 'UTF-8//IGNORE', $data);
            }
        }
        
        return $data;
    }

    // Multipart - iterate through parts
    if ($structure->type == 1 && !empty($structure->parts)) {
        // Priority 1: Look for PLAIN text
        foreach ($structure->parts as $index => $sub_structure) {
            $current_part_number = $part_number ? $part_number . "." . ($index + 1) : ($index + 1);
            if ($sub_structure->type == 0 && $sub_structure->subtype == 'PLAIN') {
                return getIMAPBody($mbox, $mail_id, $sub_structure, $current_part_number);
            }
        }
        
        // Priority 2: Recurse into first part if no plain text found at this level
        foreach ($structure->parts as $index => $sub_structure) {
            $current_part_number = $part_number ? $part_number . "." . ($index + 1) : ($index + 1);
            $found = getIMAPBody($mbox, $mail_id, $sub_structure, $current_part_number);
            if ($found !== null) return $found;
        }
    }

    return null;
}

$importedCount = 0;
if ($emails) {
    arsort($emails); // Newest first
    
    foreach ($emails as $mail_id) {
        $overview = imap_fetch_overview($mbox, $mail_id, 0);
        if (empty($overview)) continue;
        $overview = $overview[0];
        
        $structure = imap_fetchstructure($mbox, $mail_id);
        $body = getIMAPBody($mbox, $mail_id, $structure);
        
        if ($body === null) {
            $body = "(Nelze načíst obsah e-mailu)";
        }

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

        }
    }
}

imap_close($mbox);

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

