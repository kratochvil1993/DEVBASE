<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = null;
$success = false;

if ($action === 'add_todo' || $action === 'edit_todo') {
    $text = $_POST['text'] ?? '';
    $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
    $deadline = $_POST['deadline'] ?? null;
    $deadline_time = $_POST['deadline_time'] ?? null;
    $is_locked = isset($_POST['is_locked']) ? 1 : 0;
    $todo_id = $action === 'edit_todo' ? ($_POST['todo_id'] ?? null) : null;
    $note = $_POST['note'] ?? null;
    $parent_id = $_POST['parent_id'] ?? null;

    if (empty($text)) {
        echo json_encode(['status' => 'error', 'message' => 'Chybí text úkolu.']);
        exit;
    }

    $id = saveTodo($text, $tags, $todo_id, $is_locked, $deadline, $deadline_time, $note, $parent_id);
    $success = (bool)$id;
} elseif ($action === 'toggle_pin') {
    $id = $_POST['todo_id'] ?? null;
    $success = $id && toggleTodoPin($id);
} elseif ($action === 'archive_todo') {
    $id = $_POST['todo_id'] ?? null;
    $success = $id && archiveTodo($id, 1);
} elseif ($action === 'unarchive_todo') {
    $id = $_POST['todo_id'] ?? null;
    $success = $id && archiveTodo($id, 0);
} elseif ($action === 'delete_todo') {
    $id = $_POST['todo_id'] ?? null;
    $success = $id && deleteTodo($id);
} elseif ($action === 'empty_archive') {
    global $conn;
    $success = $conn->exec("DELETE FROM todos WHERE is_archived = 1");
}

if ($success) {
    // Success block - prepare supplemental data
    $stats = getGlobalStats();
    
    ob_start();
    include '../includes/header_notifications.php';
    $nav_notifications_html = ob_get_clean();

    // Preparing the main response
    $response = [
        'status' => 'success',
        'id' => $id,
        'message' => 'Akce byla úspěšná.',
        'stats' => $stats,
        'nav_notifications_html' => $nav_notifications_html
    ];

    // Specific data based on action
    if ($action === 'add_todo' || $action === 'edit_todo' || $action === 'toggle_pin') {
        $todo = getTodo($id);
        if ($todo) {
            ob_start();
            include '../includes/todo_item_template.php';
            $response['html'] = ob_get_clean();
            if ($action === 'toggle_pin') $response['is_pinned'] = (bool)$todo['is_pinned'];
        }
    }

    echo json_encode($response);
} else {
    if (!$action) {
         echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Akce se nezdařila.']);
    }
}
