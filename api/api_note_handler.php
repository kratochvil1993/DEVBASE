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
        $note = getNote($saved_id);

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
        $note = getNote($id);

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
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    if ($id && archiveNote($id, $status)) {
        $msg = $status ? 'Poznámka archivována.' : 'Poznámka byla obnovena z archivu.';
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při (de)archivaci.']);
    }
} elseif ($action === 'delete_note') {
    $id = $_POST['note_id'] ?? null;
    if ($id && deleteNote($id)) {
        echo json_encode(['status' => 'success', 'message' => 'Poznámka smazána.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při mazání.']);
    }
} elseif ($action === 'move_to_notes') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
    $scratchpad_id = $_POST['scratchpad_id'] ?? null;
    
    if ($title && $content && $scratchpad_id) {
        $saved_id = saveNote($title, $content, null, $tags);
        if ($saved_id) {
            deleteScratchpad($scratchpad_id);
            echo json_encode(['status' => 'success', 'message' => 'Poznámka byla vytvořena a draft smazán.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit poznámku.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chybějící data.']);
    }
} elseif ($action === 'list_notes') {
    $notes = getAllNotes('custom', 0); // active notes
    $result = [];
    foreach ($notes as $note) {
        $result[] = [
            'id' => $note['id'],
            'title' => $note['title']
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $result]);
} elseif ($action === 'append_to_note') {
    $note_id = $_POST['note_id'] ?? null;
    $scratchpad_id = $_POST['scratchpad_id'] ?? null;
    $content = $_POST['content'] ?? '';

    if ($note_id && $scratchpad_id && $content) {
        $note = getNote($note_id);
        if ($note) {
            $new_content = $note['content'] . "<br><hr><br>" . $content;
            if (saveNote($note['title'], $new_content, $note['language_id'], array_column($note['tags'], 'id'), $note_id)) {
                deleteScratchpad($scratchpad_id);
                echo json_encode(['status' => 'success', 'message' => 'Draft byl připsán k poznámce a smazán.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit poznámku.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Původní poznámka nebyla nalezena.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chybějící data.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
