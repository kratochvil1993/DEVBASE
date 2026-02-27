<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

try {
    $type = $_GET['type'] ?? 'code';
    $prefix = $type === 'note' ? 'Nápad ' : 'Draft ';
    
    $scratchpads = getAllScratchpads($type);
    $name = $prefix . (count($scratchpads) + 1);
    $new_id = createScratchpad($name, $type);
    
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
