<?php
require_once '../includes/functions.php';
checkApiSecurity();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Jen POST požadavky.']);
    exit;
}

$action = $_POST['action'] ?? '';
$layout = $_POST['layout'] ?? 'card'; // Default to card layout
$template = ($layout === 'row') ? '../includes/manage_snippet_row.php' : '../includes/snippet_item_template.php';

if ($action === 'add_snippet' || $action === 'edit_snippet') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $code = $_POST['code'] ?? '';
    $language_id = $_POST['language_id'] ?? null;
    $tags = isset($_POST['tags']) ? (array)$_POST['tags'] : [];
    $is_locked = isset($_POST['is_locked']) ? 1 : 0;
    $snippet_id = $action === 'edit_snippet' ? ($_POST['snippet_id'] ?? null) : (!empty($_POST['snippet_id']) ? $_POST['snippet_id'] : null);

    if (empty($title) || empty($code)) {
        echo json_encode(['status' => 'error', 'message' => 'Chybí název nebo kód snipetu.']);
        exit;
    }

    $id = saveSnippet($title, $description, $code, $language_id, $tags, $snippet_id, $is_locked);

    if ($id) {
        $snippets = getAllSnippets();
        $snippet = null;
        foreach ($snippets as $s) {
            if ($s['id'] == $id) {
                $snippet = $s;
                break;
            }
        }

        if ($snippet) {
            ob_start();
            include $template;
            $html = ob_get_clean();

            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'is_new' => $action === 'add_snippet' || empty($snippet_id),
                'html' => $html,
                'is_pinned' => (bool)($snippet['is_pinned'] ?? false),
                'message' => 'Snipet byl uložen.'
            ]);
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Snipet byl uložen, ale nepodařilo se jej načíst pro zobrazení.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nepodařilo se uložit snipet do databáze.']);
    }
} elseif ($action === 'toggle_pin') {
    $id = $_POST['snippet_id'] ?? null;
    if ($id && toggleSnippetPin($id)) {
        $snippets = getAllSnippets();
        $snippet = null;
        foreach ($snippets as $s) {
            if ($s['id'] == $id) {
                $snippet = $s;
                break;
            }
        }

        if ($snippet) {
            ob_start();
            include $template;
            $html = ob_get_clean();

            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'is_pinned' => (bool)$snippet['is_pinned'],
                'html' => $html,
                'message' => 'Stav připnutí změněn.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Stav změněn, ale nepodařilo se jej načíst pro zobrazení.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při připínání.']);
    }
} elseif ($action === 'delete_snippet') {
    $id = $_POST['snippet_id'] ?? null;
    if ($id && deleteSnippet($id)) {
        echo json_encode(['status' => 'success', 'message' => 'Snipet smazán.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Chyba při mazání.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Neznámá akce.']);
}
