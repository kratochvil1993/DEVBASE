<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_note') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;
    $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
    $is_locked = isset($_POST['is_locked']) ? 1 : 0;

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Chybí název poznámky.']);
        exit;
    }

    $saved_id = saveNote($title, $content, null, $tags, $id, $is_locked);

    if ($saved_id) {
        // Fetch all notes to find the updated/added one
        $notes = getAllNotes('custom');
        $note = null;
        foreach ($notes as $n) {
            if ($n['id'] == $saved_id) {
                $note = $n;
                break;
            }
        }

        if ($note) {
            ob_start();
            $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
            $tagData = implode(',', $tagNames);
            
            $template = $_POST['template'] ?? 'card';
            if ($template === 'manage_row') {
                include '../includes/manage_note_row.php';
            } else {
                include '../includes/note_item_template.php';
            }
            $html = ob_get_clean();

            echo json_encode([
                'status' => 'success',
                'id' => $saved_id,
                'is_new' => $id === null,
                'is_pinned' => (bool)$note['is_pinned'],
                'html' => $html,
                'message' => 'Poznámka byla uložena.'
            ]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Poznámka byla uložena, ale nepodařilo se ji načíst.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit poznámku do databáze.']);
    }
} elseif ($action === 'toggle_pin') {
    $id = $_POST['note_id'] ?? null;
    if ($id && toggleNotePin($id)) {
        // Fetch the updated note
        $notes = getAllNotes('custom');
        $note = null;
        foreach ($notes as $n) {
            if ($n['id'] == $id) {
                $note = $n;
                break;
            }
        }

        if ($note) {
            ob_start();
            $tagNames = array_map(function($t) { return $t['name']; }, $note['tags']);
            $tagData = implode(',', $tagNames);
            
            $template = $_POST['template'] ?? 'card';
            if ($template === 'manage_row') {
                include '../includes/manage_note_row.php';
            } else {
                include '../includes/note_item_template.php';
            }
            $html = ob_get_clean();

            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'is_pinned' => (bool)$note['is_pinned'],
                'html' => $html,
                'message' => 'Stav připnutí změněn.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Stav změněn, ale nepodařilo se načíst data.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při pinu.']);
    }
} elseif ($action === 'archive_note') {
    $id = $_POST['note_id'] ?? null;
    if ($id && archiveNote($id, 1)) {
        echo json_encode(['status' => 'success', 'message' => 'Poznámka archivována.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při archivaci.']);
    }
} elseif ($action === 'delete_note') {
    $id = $_POST['note_id'] ?? null;
    if ($id && deleteNote($id)) {
        echo json_encode(['status' => 'success', 'message' => 'Poznámka smazána.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při mazání.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
