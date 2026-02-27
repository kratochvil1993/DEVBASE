<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

try {
    $scratchpads = getAllScratchpads('code');
    $name = 'Draft ' . (count($scratchpads) + 1);
    $new_id = createScratchpad($name, 'code');
    
    if ($new_id) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $new_id,
                'name' => $name,
                'content' => ''
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se vytvořit draft.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
