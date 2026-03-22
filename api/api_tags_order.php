<?php
require_once '../includes/functions.php';
checkApiSecurity();


header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['order']) && is_array($data['order'])) {
    foreach ($data['order'] as $item) {
        $id = (int)$item['id'];
        $order = (int)$item['order'];
        $conn->prepare("UPDATE tags SET sort_order = :order WHERE id = :id")->execute(['order' => (int)$item['order'], 'id' => (int)$item['id']]);
    }
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
?>
