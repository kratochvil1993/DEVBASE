<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Žádná data k uložení.']);
    exit;
}

$id = $data['id'] ?? null;
$content = $data['content'] ?? null;
$name = $data['name'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí ID draftu.']);
    exit;
}

try {
    if ($content !== null) {
        saveScratchpadContent($content, $id);
    }

    if ($name !== null && !empty($name)) {
        renameScratchpad($id, $name);
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Automaticky uloženo',
        'time' => date('H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Chyba při ukládání: ' . $e->getMessage()
    ]);
}
