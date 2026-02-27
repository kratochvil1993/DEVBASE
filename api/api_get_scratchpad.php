<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí ID draftu.']);
    exit;
}

$id = (int)$_GET['id'];
$scratchpad = getScratchpad($id);

if ($scratchpad) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $scratchpad['id'],
            'name' => $scratchpad['name'],
            'content' => $scratchpad['content']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Draft nenalezen.']);
}
