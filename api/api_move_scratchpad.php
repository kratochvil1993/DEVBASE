<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$scratchpad_id = (int)($_POST['scratchpad_id'] ?? 0);

if (!$scratchpad_id) {
    echo json_encode(['status' => 'error', 'message' => 'Chybí ID draftu']);
    exit;
}

if ($action === 'move_to_notes') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = $_POST['tags'] ?? [];
    
    if ($title && $content) {
        $saved_id = saveNote($title, $content, null, $tags);
        if ($saved_id) {
            deleteScratchpad($scratchpad_id);
            echo json_encode(['status' => 'success', 'message' => 'Přesunuto do poznámek']);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Chyba při ukládání poznámky']);
} elseif ($action === 'move_to_snippets') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $code = $_POST['code'] ?? '';
    $language_id = $_POST['language_id'] ?? null;
    $tags = $_POST['tags'] ?? [];
    $is_locked = (isset($_POST['is_locked']) && $_POST['is_locked'] == '1') ? 1 : 0;
    
    if ($title && $code) {
        $saved_id = saveSnippet($title, $description, $code, $language_id, $tags, null, $is_locked);
        if ($saved_id) {
            deleteScratchpad($scratchpad_id);
            echo json_encode(['status' => 'success', 'message' => 'Přesunuto do snippetů']);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Chyba při ukládání snippetu']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce']);
}
