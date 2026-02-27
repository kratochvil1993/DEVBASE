<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí ID draftu.']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $success = deleteScratchpad($id);
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Draft smazán.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se smazat draft.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
