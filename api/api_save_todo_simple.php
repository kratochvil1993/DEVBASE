<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$text = $data['text'] ?? '';
$deadline = $data['deadline'] ?? null;

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí text úkolu.']);
    exit;
}

// Get first tag for todo if available, as a default
$allTags = getAllTags('todo');
$tags = !empty($allTags) ? [$allTags[0]['id']] : [];

$id = saveTodo($text, $tags, null, 0, $deadline);

if ($id) {
    echo json_encode(['status' => 'success', 'id' => $id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit úkol.']);
}
