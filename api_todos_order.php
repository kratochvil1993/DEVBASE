<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['order']) && is_array($data['order'])) {
        foreach ($data['order'] as $item) {
            updateTodoOrder($item['id'], $item['order']);
        }
        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error']);
?>
