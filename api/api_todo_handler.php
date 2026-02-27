<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_todo' || $action === 'edit_todo') {
    $text = $_POST['text'] ?? '';
    $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
    $deadline = $_POST['deadline'] ?? null;
    $is_locked = isset($_POST['is_locked']) ? 1 : 0;
    $todo_id = $action === 'edit_todo' ? ($_POST['todo_id'] ?? null) : null;

    if (empty($text)) {
        echo json_encode(['status' => 'error', 'message' => 'Chybí text úkolu.']);
        exit;
    }

    $id = saveTodo($text, $tags, $todo_id, $is_locked, $deadline);

    if ($id) {
        // Fetch the full todo object to render the template
        $todos = getAllTodos(0);
        $todo = null;
        foreach ($todos as $t) {
            if ($t['id'] == $id) {
                $todo = $t;
                break;
            }
        }

        if ($todo) {
            ob_start();
            include '../includes/todo_item_template.php';
            $html = ob_get_clean();

            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'is_new' => $action === 'add_todo',
                'html' => $html,
                'message' => $action === 'add_todo' ? 'Úkol byl přidán.' : 'Úkol byl upraven.'
            ]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Úkol byl uložen, ale nepodařilo se jej načíst pro zobrazení.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit úkol do databáze.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
