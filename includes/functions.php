<?php
require_once __DIR__ . '/db.php';

// Check if database and tables are created and up to date
$current_db_version = '1.2.2';

// Proaktivní kontrola existence tabulek a verze
// Proaktivní kontrola existence tabulek a verze
$needs_init = false;
try {
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        $check_table = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='settings'");
    } else {
        $check_table = $conn->query("SHOW TABLES LIKE 'settings'");
    }
    
    if (!$check_table || !$check_table->fetch()) {
        $needs_init = true;
    } else {
        // Pokud settings existuje, zkontrolujeme verzi
        $db_ver = getSetting('db_version', '0');
        if (version_compare($db_ver, $current_db_version, '<')) {
            $needs_init = true;
        }
    }
} catch (Exception $e) {
    $needs_init = true;
}


if ($needs_init) {
    // Pokud je potřeba init, ale pouze pokud nejde o AJAX a jsme v browseru
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if (!$is_ajax && PHP_SAPI !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'init_db.php') {
        $redirect_path = file_exists('includes/init_db.php') ? 'includes/init_db.php' : '../includes/init_db.php';
        header('Location: ' . $redirect_path);
        exit;
    }
}


// Funkce aplikace...

function getAllSnippets($search = '') {
    global $conn;
    $sql = "SELECT s.*, l.name as language_name, l.prism_class 
            FROM snippets s 
            LEFT JOIN languages l ON s.language_id = l.id";
    
    if (!empty($search)) {
        $search_val = "%$search%";
        $sql .= " WHERE s.title LIKE :search OR s.code LIKE :search2 OR s.description LIKE :search3";
        $sql .= " ORDER BY s.is_pinned DESC, s.sort_order ASC, s.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['search' => $search_val, 'search2' => $search_val, 'search3' => $search_val]);
        $result = $stmt;
    } else {
        $sql .= " ORDER BY s.is_pinned DESC, s.sort_order ASC, s.created_at DESC";
        $result = $conn->query($sql);
    }
    
    $snippets = [];
    while ($row = $result->fetch()) {
        $row['tags'] = getSnippetTags($row['id']);
        $snippets[] = $row;
    }
    return $snippets;
}


function getSnippetTags($snippet_id) {
    global $conn;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN snippet_tags st ON t.id = st.tag_id 
            WHERE st.snippet_id = :id 
            ORDER BY t.sort_order ASC, t.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => (int)$snippet_id]);
    $tags = [];
    while ($row = $stmt->fetch()) {
        $tags[] = $row;
    }
    return $tags;
}


function getNoteTags($note_id) {
    global $conn;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN note_tags nt ON t.id = nt.tag_id 
            WHERE nt.note_id = :id
            ORDER BY t.sort_order ASC, t.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => (int)$note_id]);
    $tags = [];
    while ($row = $stmt->fetch()) {
        $tags[] = $row;
    }
    return $tags;
}


function getTodoTags($todo_id) {
    global $conn;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN todo_tags tt ON t.id = tt.tag_id 
            WHERE tt.todo_id = :id
            ORDER BY t.sort_order ASC, t.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => (int)$todo_id]);
    $tags = [];
    while ($row = $stmt->fetch()) {
        $tags[] = $row;
    }
    return $tags;
}


function getAllTags($type = 'snippet') {
    global $conn;
    $sql = "SELECT * FROM tags WHERE type = :type ORDER BY sort_order ASC, name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['type' => $type]);
    return $stmt->fetchAll();
}


function getAllLanguages() {
    global $conn;
    $sql = "SELECT * FROM languages ORDER BY name ASC";
    return $conn->query($sql)->fetchAll();
}


function saveTag($name, $color, $type = 'snippet', $id = null) {
    global $conn;
    if ($id) {
        $sql = "UPDATE tags SET name = :name, color = :color, type = :type WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['name' => $name, 'color' => $color, 'type' => $type, 'id' => (int)$id]);
    } else {
        if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
            $sql = "INSERT INTO tags (name, color, type) VALUES (:name, :color, :type) 
                    ON CONFLICT(name, type) DO UPDATE SET color = EXCLUDED.color";
        } else {
            $sql = "INSERT INTO tags (name, color, type) VALUES (:name, :color, :type) 
                    ON DUPLICATE KEY UPDATE color = :color2";
        }
        $stmt = $conn->prepare($sql);
        $params = ['name' => $name, 'color' => $color, 'type' => $type];
        if (defined('DB_TYPE') && DB_TYPE !== 'sqlite') $params['color2'] = $color;
        return $stmt->execute($params);
    }
}


function deleteTag($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM tags WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function saveLanguage($name, $prism_class, $id = null) {
    global $conn;
    if ($id) {
        $sql = "UPDATE languages SET name = :name, prism_class = :prism_class WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['name' => $name, 'prism_class' => $prism_class, 'id' => (int)$id]);
    } else {
        $sql = "INSERT INTO languages (name, prism_class) VALUES (:name, :prism_class)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['name' => $name, 'prism_class' => $prism_class]);
    }
}


function deleteLanguage($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM languages WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function saveSnippet($title, $description, $code, $language_id, $tags = [], $id = null, $is_locked = 0) {
    global $conn;
    $language_id = $language_id ?: null;
    $is_locked = (int)$is_locked;

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE snippets SET title = :title, description = :description, code = :code, language_id = :language_id, is_locked = :is_locked WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['title' => $title, 'description' => $description, 'code' => $code, 'language_id' => $language_id, 'is_locked' => $is_locked, 'id' => $id]);
    } else {
        $resSort = $conn->query("SELECT MIN(sort_order) as min_sort FROM snippets")->fetch();
        $next_sort = $resSort['min_sort'] !== null ? (int)$resSort['min_sort'] - 1 : 0;
        $sql = "INSERT INTO snippets (title, description, code, language_id, sort_order, is_locked) VALUES (:title, :description, :code, :language_id, :sort_order, :is_locked)";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['title' => $title, 'description' => $description, 'code' => $code, 'language_id' => $language_id, 'sort_order' => $next_sort, 'is_locked' => $is_locked]);
    }

    if ($res) {
        $snippet_id = $id ?: $conn->lastInsertId();
        $conn->prepare("DELETE FROM snippet_tags WHERE snippet_id = :id")->execute(['id' => $snippet_id]);
        $stmtTag = $conn->prepare("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES (:sid, :tid)");
        foreach ($tags as $tag_id) {
            $stmtTag->execute(['sid' => $snippet_id, 'tid' => (int)$tag_id]);
        }
        return $snippet_id;
    }
    return false;
}


function deleteSnippet($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM snippets WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function toggleSnippetPin($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE snippets SET is_pinned = 1 - is_pinned WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function updateSnippetOrder($id, $order) {
    global $conn;
    $stmt = $conn->prepare("UPDATE snippets SET sort_order = :order WHERE id = :id");
    return $stmt->execute(['order' => (int)$order, 'id' => (int)$id]);
}


function getAllNotes($sort = 'custom', $archive_status = 0) {
    global $conn;
    $orderBy = "n.is_pinned DESC, n.sort_order ASC, n.created_at DESC";
    
    switch ($sort) {
        case 'oldest': $orderBy = "n.is_pinned DESC, n.created_at ASC"; break;
        case 'newest': $orderBy = "n.is_pinned DESC, n.created_at DESC"; break;
        case 'alpha_asc': $orderBy = "n.is_pinned DESC, n.title ASC"; break;
        case 'alpha_desc': $orderBy = "n.is_pinned DESC, n.title DESC"; break;
    }
    
    $whereClause = "";
    $params = [];
    if ($archive_status !== 2) {
        $whereClause = "WHERE n.is_archived = :status";
        $params['status'] = (int)$archive_status;
    }

    $sql = "SELECT n.*, l.name as language_name, l.prism_class 
            FROM notes n
            LEFT JOIN languages l ON n.language_id = l.id
            $whereClause
            ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $notes = [];
    $noteIds = [];
    while ($row = $stmt->fetch()) {
        $row['tags'] = [];
        $notes[$row['id']] = $row;
        $noteIds[] = $row['id'];
    }

    if (!empty($noteIds)) {
        $idsStr = implode(',', array_map('intval', $noteIds));
        $tagsSql = "SELECT nt.note_id, t.id, t.name, t.color, t.sort_order 
                    FROM note_tags nt 
                    JOIN tags t ON nt.tag_id = t.id 
                    WHERE nt.note_id IN ($idsStr)
                    ORDER BY t.sort_order ASC, t.name ASC";
        $tagsRes = $conn->query($tagsSql);
        while ($tagRow = $tagsRes->fetch()) {
            $notes[$tagRow['note_id']]['tags'][] = $tagRow;
        }
    }
    return array_values($notes);
}


function getNote($id) {
    global $conn;
    $sql = "SELECT n.*, l.name as language_name, l.prism_class 
            FROM notes n
            LEFT JOIN languages l ON n.language_id = l.id
            WHERE n.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => (int)$id]);
    if ($row = $stmt->fetch()) {
        $row['tags'] = getNoteTags($row['id']);
        return $row;
    }
    return null;
}


function archiveNote($id, $status = 1) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notes SET is_archived = :status WHERE id = :id");
    return $stmt->execute(['status' => (int)$status, 'id' => (int)$id]);
}


function toggleNotePin($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notes SET is_pinned = 1 - is_pinned WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function saveNote($title, $content, $language_id = null, $tags = [], $id = null, $is_locked = 0) {
    global $conn;
    $is_locked = (int)$is_locked;

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE notes SET title = :title, content = :content, language_id = :language_id, is_locked = :is_locked WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['title' => $title, 'content' => $content, 'language_id' => $language_id, 'is_locked' => $is_locked, 'id' => $id]);
    } else {
        $resSort = $conn->query("SELECT MIN(sort_order) as min_sort FROM notes")->fetch();
        $next_sort = $resSort['min_sort'] !== null ? (int)$resSort['min_sort'] - 1 : 0;
        $sql = "INSERT INTO notes (title, content, sort_order, language_id, is_locked) VALUES (:title, :content, :sort_order, :language_id, :is_locked)";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['title' => $title, 'content' => $content, 'sort_order' => $next_sort, 'language_id' => $language_id, 'is_locked' => $is_locked]);
    }

    if ($res) {
        $note_id = $id ?: $conn->lastInsertId();
        $conn->prepare("DELETE FROM note_tags WHERE note_id = :id")->execute(['id' => $note_id]);
        $stmtTag = $conn->prepare("INSERT INTO note_tags (note_id, tag_id) VALUES (:nid, :tid)");
        foreach ($tags as $tag_id) {
            $stmtTag->execute(['nid' => $note_id, 'tid' => (int)$tag_id]);
        }
        return $note_id;
    }
    return false;
}


function updateNoteOrder($id, $order) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notes SET sort_order = :order WHERE id = :id");
    return $stmt->execute(['order' => (int)$order, 'id' => (int)$id]);
}


function deleteNote($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function getSetting($key, $default = null) {
    global $conn;
    if (!isset($GLOBALS['_settings_cache'])) {
        $GLOBALS['_settings_cache'] = [];
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $result->fetch()) {
            $GLOBALS['_settings_cache'][$row['setting_key']] = $row['setting_value'];
        }
    }
    return array_key_exists($key, $GLOBALS['_settings_cache']) ? $GLOBALS['_settings_cache'][$key] : $default;
}

function updateSetting($key, $value) {
    global $conn;
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value";
    } else {
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value2";
    }
    $stmt = $conn->prepare($sql);
    $params = ['key' => $key, 'value' => $value];
    if (defined('DB_TYPE') && DB_TYPE !== 'sqlite') $params['value2'] = $value;
    $result = $stmt->execute($params);
    if ($result) {
        $GLOBALS['_settings_cache'][$key] = $value;
    }
    return $result;
}


// Table structure is now handled by schema.sql


function getAllTodos($archive_status = 0, $asTree = true, $asIndexedFlat = false) {
    global $conn;
    $archive_status = (int)$archive_status;

    $sql = "SELECT * FROM todos WHERE is_archived = :status ORDER BY is_pinned DESC, sort_order ASC, created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['status' => (int)$archive_status]);
    $allTodos = [];
    $todoIds = [];
    
    while ($row = $stmt->fetch()) {
        $row['tags'] = [];
        $row['subtasks'] = [];
        $allTodos[$row['id']] = $row;
        $todoIds[] = $row['id'];
    }


    if (!empty($todoIds)) {
        $idsStr = implode(',', array_map('intval', $todoIds));
        $tagsSql = "SELECT tt.todo_id, t.id, t.name, t.color, t.sort_order 
                    FROM todo_tags tt 
                    JOIN tags t ON tt.tag_id = t.id 
                    WHERE tt.todo_id IN ($idsStr)
                    ORDER BY t.sort_order ASC, t.name ASC";
        $tagsRes = $conn->query($tagsSql);
        while ($tagRow = $tagsRes->fetch()) {
            $todoId = $tagRow['todo_id'];
            unset($tagRow['todo_id']);
            if (isset($allTodos[$todoId])) {
                $allTodos[$todoId]['tags'][] = $tagRow;
            }
        }
    }

    
    // First pass: link children to parents
    foreach ($allTodos as $id => &$todo) {
        if (!empty($todo['parent_id']) && isset($allTodos[$todo['parent_id']])) {
            $allTodos[$todo['parent_id']]['subtasks'][] = &$todo;
        }
    }
    unset($todo);

    if ($asIndexedFlat) {
        return $allTodos;
    }

    if (!$asTree) {
        return array_values($allTodos);
    }
    
    $tree = [];
    // Second pass: extract roots
    foreach ($allTodos as $id => &$todo) {
        if (empty($todo['parent_id']) || !isset($allTodos[$todo['parent_id']])) {
            $tree[] = $todo;
        }
    }
    unset($todo);
    
    return $tree;
}

function getTodo($id) {
    // Get all active and archived
    $all = getAllTodos(0, true, true);
    if (isset($all[$id])) return $all[$id];
    
    $archived = getAllTodos(1, true, true);
    if (isset($archived[$id])) return $archived[$id];
    
    return null;
}

function saveTodo($text, $tags = [], $id = null, $is_locked = 0, $deadline = null, $deadline_time = null, $note = null, $parent_id = null) {
    global $conn;
    $is_locked = (int)$is_locked;
    if ($deadline === null && !empty($_POST['deadline'])) $deadline = $_POST['deadline'];
    if ($deadline_time === null && !empty($_POST['deadline_time'])) $deadline_time = $_POST['deadline_time'];
    if ($note === null && isset($_POST['note'])) $note = $_POST['note'];
    if ($parent_id === null && !empty($_POST['parent_id'])) $parent_id = (int)$_POST['parent_id'];
    if (empty($deadline) && !empty($deadline_time)) $deadline = date('Y-m-d');
    
    $deadline = !empty($deadline) ? $deadline : null;
    $deadline_time = !empty($deadline_time) ? $deadline_time : null;
    $note = ($note !== null && $note !== '') ? $note : null;
    $parent_id = $parent_id ? (int)$parent_id : null;
    
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE todos SET text = :text, deadline = :deadline, deadline_time = :deadline_time, note = :note, is_locked = :is_locked, parent_id = :parent_id WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['text' => $text, 'deadline' => $deadline, 'deadline_time' => $deadline_time, 'note' => $note, 'is_locked' => $is_locked, 'parent_id' => $parent_id, 'id' => $id]);
    } else {
        $resSort = $conn->query("SELECT MIN(sort_order) as min_sort FROM todos")->fetch();
        $next_sort = $resSort['min_sort'] !== null ? (int)$resSort['min_sort'] - 1 : 0;
        $sql = "INSERT INTO todos (text, deadline, deadline_time, note, sort_order, is_locked, parent_id) VALUES (:text, :deadline, :deadline_time, :note, :sort_order, :is_locked, :parent_id)";
        $stmt = $conn->prepare($sql);
        $res = $stmt->execute(['text' => $text, 'deadline' => $deadline, 'deadline_time' => $deadline_time, 'note' => $note, 'sort_order' => $next_sort, 'is_locked' => $is_locked, 'parent_id' => $parent_id]);
    }
    
    if ($res) {
        $todo_id = $id ? $id : $conn->lastInsertId();
        $conn->prepare("DELETE FROM todo_tags WHERE todo_id = :id")->execute(['id' => $todo_id]);
        if (is_array($tags)) {
            $stmtTag = $conn->prepare("INSERT INTO todo_tags (todo_id, tag_id) VALUES (:sid, :tid)");
            foreach ($tags as $tag_id) {
                $stmtTag->execute(['sid' => $todo_id, 'tid' => (int)$tag_id]);
            }
        }
        return $todo_id;
    }
    return false;
}

function archiveTodo($id, $status = 1) {
    global $conn;
    $id = (int)$id;
    $status = (int)$status;
    if ($status == 1) {
        $conn->prepare("UPDATE todos SET is_archived = 1 WHERE parent_id = :id")->execute(['id' => $id]);
    }
    $stmt = $conn->prepare("UPDATE todos SET is_archived = :status WHERE id = :id");
    return $stmt->execute(['status' => $status, 'id' => $id]);
}


function toggleTodoPin($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE todos SET is_pinned = 1 - is_pinned WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}


function updateTodoOrder($id, $order) {
    global $conn;
    $stmt = $conn->prepare("UPDATE todos SET sort_order = :order WHERE id = :id");
    return $stmt->execute(['order' => (int)$order, 'id' => (int)$id]);
}


function deleteTodo($id) {
    global $conn;
    $id = (int)$id;
    $conn->prepare("DELETE FROM todos WHERE parent_id = :id")->execute(['id' => $id]);
    $stmt = $conn->prepare("DELETE FROM todos WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}


function getGlobalStats() {
    global $conn;
    $stats = [
        'total_snippets' => (int)$conn->query("SELECT COUNT(*) FROM snippets")->fetchColumn(),
        'total_notes' => (int)$conn->query("SELECT COUNT(*) FROM notes WHERE is_archived = 0")->fetchColumn(),
        'total_todos' => (int)$conn->query("SELECT COUNT(*) FROM todos WHERE is_archived = 0")->fetchColumn(),
        'total_code_drafts' => (int)$conn->query("SELECT COUNT(*) FROM scratchpads WHERE type = 'code'")->fetchColumn(),
        'total_note_drafts' => (int)$conn->query("SELECT COUNT(*) FROM scratchpads WHERE type = 'note'")->fetchColumn(),
        'last_added' => $conn->query("SELECT title, created_at FROM (
            SELECT title, created_at FROM snippets
            UNION ALL
            SELECT title, created_at FROM notes
        ) as combined ORDER BY created_at DESC LIMIT 1")->fetch(),
        'top_tags' => [],
        'total_inbox_new' => (int)$conn->query("SELECT COUNT(*) FROM inbox_items WHERE is_seen = 0")->fetchColumn()
    ];
    
    $resTags = $conn->query("SELECT t.name, t.color, (
        (SELECT COUNT(*) FROM snippet_tags st WHERE st.tag_id = t.id) +
        (SELECT COUNT(*) FROM note_tags nt WHERE nt.tag_id = t.id) +
        (SELECT COUNT(*) FROM todo_tags tt WHERE tt.tag_id = t.id)
    ) as usage_count
    FROM tags t
    WHERE (
        (SELECT COUNT(*) FROM snippet_tags st WHERE st.tag_id = t.id) +
        (SELECT COUNT(*) FROM note_tags nt WHERE nt.tag_id = t.id) +
        (SELECT COUNT(*) FROM todo_tags tt WHERE tt.tag_id = t.id)
    ) > 0
    ORDER BY usage_count DESC
    LIMIT 4");
    while ($row = $resTags->fetch()) {
        $stats['top_tags'][] = $row;
    }
    return $stats;
}


function getTodoReminders() {
    global $conn;
    $today = date('Y-m-d');
    $now_time = date('H:i:s');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $sql = "SELECT * FROM todos 
            WHERE is_archived = 0 
            AND deadline IS NOT NULL 
            AND deadline <= :tomorrow 
            ORDER BY deadline ASC, deadline_time ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['tomorrow' => $tomorrow]);
    $reminders = ['critical' => [], 'warning' => []];
    while ($row = $stmt->fetch()) {
        if ($row['deadline'] < $today) {
            $reminders['critical'][] = $row;
        } elseif ($row['deadline'] == $today) {
            if ($row['deadline_time'] !== null && $row['deadline_time'] > $now_time) {
                $reminders['warning'][] = $row;
            } else {
                $reminders['critical'][] = $row;
            }
        } elseif ($row['deadline'] == $tomorrow) {
            $reminders['warning'][] = $row;
        }
    }
    return $reminders;
}


function isAppLocked() {
    // Pokud není nastaveno heslo, považujeme aplikaci za nezabezpečenou (první spuštění)
    $stored_password = getSetting('app_password');
    if (empty($stored_password)) {
        return false;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return !isset($_SESSION['app_unlocked']) || $_SESSION['app_unlocked'] !== true;
}

function checkApiSecurity() {
    if (isAppLocked()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Application locked']);
        exit;
    }
}

function verifyLogin($username, $password) {
    if (empty($username) || empty($password)) return false;
    
    $stored_username = getSetting('app_username', 'admin');
    $stored_password = getSetting('app_password');
    
    // Pokud heslo v DB není (čistá instalace), nastavíme admin/admin
    if (empty($stored_password)) {
        $default_hash = password_hash('admin', PASSWORD_DEFAULT);
        updateSetting('app_username', 'admin');
        updateSetting('app_password', $default_hash);
        updateSetting('security_enabled', '1');
        $stored_username = 'admin';
        $stored_password = $default_hash;
    }

    // Porovnání jména (case-insensitive) a hesla
    if (strtolower($username) === strtolower($stored_username) && password_verify($password, $stored_password)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['app_unlocked'] = true;
        $_SESSION['username'] = $stored_username;
        return true;
    }
    return false;
}

// Pro zpětnou kompatibilitu, pokud by to někde zůstalo
function verifyAppPassword($password) {
    $username = getSetting('app_username', 'admin');
    return verifyLogin($username, $password);
}

function getTodoSummariesForAI() {
    global $conn;
    $today = date('Y-m-d');
    
    $sql = "SELECT t.*, GROUP_CONCAT(tg.name) as tag_names 
            FROM todos t 
            LEFT JOIN todo_tags tt ON t.id = tt.todo_id 
            LEFT JOIN tags tg ON tt.tag_id = tg.id 
            WHERE t.is_archived = 0 
            GROUP BY t.id 
            ORDER BY t.is_pinned DESC, t.deadline ASC, t.sort_order ASC";
    
    $result = $conn->query($sql);
    $output = "SOUČASNÉ DATUM: " . $today . "\n\nNEVYŘÍZENÉ ÚKOLY:\n";
    $rowCount = 0;
    while ($row = $result->fetch()) {
        $rowCount++;
        $status = "Standardní";
        if ($row['deadline']) {
            if ($row['deadline'] < $today) $status = "PO TERMÍNU!";
            elseif ($row['deadline'] == $today) $status = "DNES";
        }
        $tags = $row['tag_names'] ? "[" . $row['tag_names'] . "]" : "";
        $pinned = $row['is_pinned'] ? "(PŘIPNUTO)" : "";
        $deadline_str = ($row['deadline'] ?: "neuveden") . ($row['deadline_time'] ? " v " . substr($row['deadline_time'], 0, 5) : "");
        $output .= "- $pinned " . $row['text'] . " $tags (Deadline: $deadline_str) -> $status\n";
    }
    if ($rowCount == 0) $output .= "Momentálně nemáš žádné aktivní úkoly. Skvělá práce!";
    return $output;
}


function exportAllData() {
    global $conn;
    $data = [
        'version' => '1.0',
        'export_date' => date('c'),
        'settings' => [], 'languages' => [], 'tags' => [],
        'snippets' => [], 'notes' => [], 'todos' => [],
        'scratchpads' => [], 'inbox_items' => []
    ];

    $res = $conn->query("SELECT * FROM settings");
    while ($row = $res->fetch()) $data['settings'][$row['setting_key']] = $row['setting_value'];

    $res = $conn->query("SELECT * FROM languages");
    while ($row = $res->fetch()) $data['languages'][] = $row;


    // Tags
    $res = $conn->query("SELECT * FROM tags");
    while ($row = $res->fetch()) $data['tags'][] = $row;

    // Snippets with tags
    $res = $conn->query("SELECT * FROM snippets");
    while ($row = $res->fetch()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM snippet_tags WHERE snippet_id = " . (int)$row['id']);
        while ($t = $tags_res->fetch()) $row['tags'][] = $t['tag_id'];
        $data['snippets'][] = $row;
    }

    // Notes with tags
    $res = $conn->query("SELECT * FROM notes");
    while ($row = $res->fetch()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM note_tags WHERE note_id = " . (int)$row['id']);
        while ($t = $tags_res->fetch()) $row['tags'][] = $t['tag_id'];
        $data['notes'][] = $row;
    }

    // Todos with tags
    $res = $conn->query("SELECT * FROM todos");
    while ($row = $res->fetch()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM todo_tags WHERE todo_id = " . (int)$row['id']);
        while ($t = $tags_res->fetch()) $row['tags'][] = $t['tag_id'];
        $data['todos'][] = $row;
    }

    // Scratchpads
    $res = $conn->query("SELECT * FROM scratchpads");
    while ($row = $res->fetch()) $data['scratchpads'][] = $row;

    // Inbox Items
    $res = $conn->query("SELECT * FROM inbox_items");
    while ($row = $res->fetch()) $data['inbox_items'][] = $row;

    return $data;
}



function importAllData($data, $mode = 'append') {
    global $conn;
    if ($mode === 'overwrite') {
        $tables = ['snippet_tags', 'note_tags', 'todo_tags', 'snippets', 'notes', 'todos', 'tags', 'languages', 'scratchpads', 'inbox_items'];
        foreach ($tables as $t) $conn->query("DELETE FROM $t");
    }

    if (!empty($data['settings'])) {
        foreach ($data['settings'] as $key => $value) {
            if ($key === 'app_password' || $key === 'security_enabled') continue;
            updateSetting($key, $value);
        }
    }

    $langMap = []; $tagMap = []; $snippetMap = []; $noteMap = []; $todoMap = []; $scratchpadMap = [];

    if (!empty($data['languages'])) {
        foreach ($data['languages'] as $lang) {
            $stmt = $conn->prepare("SELECT id FROM languages WHERE name = :name");
            $stmt->execute(['name' => $lang['name']]);
            if ($row = $stmt->fetch()) {
                $langMap[$lang['id']] = $row['id'];
            } else {
                $conn->prepare("INSERT INTO languages (name, prism_class) VALUES (:name, :prism)")
                     ->execute(['name' => $lang['name'], 'prism' => $lang['prism_class']]);
                $langMap[$lang['id']] = $conn->lastInsertId();
            }
        }
    }

    if (!empty($data['tags'])) {
        foreach ($data['tags'] as $tag) {
            $type = $tag['type'] ?? 'snippet';
            $stmt = $conn->prepare("SELECT id FROM tags WHERE name = :name AND type = :type");
            $stmt->execute(['name' => $tag['name'], 'type' => $type]);
            if ($row = $stmt->fetch()) {
                $tagMap[$tag['id']] = $row['id'];
            } else {
                $conn->prepare("INSERT INTO tags (name, color, type, sort_order) VALUES (:name, :color, :type, :sort)")
                     ->execute(['name' => $tag['name'], 'color' => $tag['color'] ?? null, 'type' => $type, 'sort' => (int)($tag['sort_order'] ?? 0)]);
                $tagMap[$tag['id']] = $conn->lastInsertId();
            }
        }
    }

    if (!empty($data['snippets'])) {
        foreach ($data['snippets'] as $snip) {
            $lang_id = isset($langMap[$snip['language_id']]) ? $langMap[$snip['language_id']] : null;
            $sql = "INSERT INTO snippets (title, description, code, language_id, is_pinned, is_locked, sort_order, created_at) 
                    VALUES (:title, :desc, :code, :lang, :pinned, :locked, :sort, :created)";
            $conn->prepare($sql)->execute([
                'title' => $snip['title'], 'desc' => $snip['description'], 'code' => $snip['code'],
                'lang' => $lang_id, 'pinned' => (int)($snip['is_pinned'] ?? 0), 'locked' => (int)($snip['is_locked'] ?? 0),
                'sort' => (int)($snip['sort_order'] ?? 0), 'created' => $snip['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $new_id = $conn->lastInsertId();
            $snippetMap[$snip['id']] = $new_id;
            if (!empty($snip['tags'])) {
                $stmtST = $conn->prepare("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES (:sid, :tid)");
                foreach ($snip['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) $stmtST->execute(['sid' => $new_id, 'tid' => $tagMap[$old_tag_id]]);
                }
            }
        }
    }

    if (!empty($data['notes'])) {
        foreach ($data['notes'] as $note) {
            $lang_id = isset($langMap[$note['language_id']]) ? $langMap[$note['language_id']] : null;
            $sql = "INSERT INTO notes (title, content, sort_order, language_id, is_archived, is_pinned, is_locked, created_at) 
                    VALUES (:title, :content, :sort, :lang, :archived, :pinned, :locked, :created)";
            $conn->prepare($sql)->execute([
                'title' => $note['title'], 'content' => $note['content'], 'sort' => (int)($note['sort_order'] ?? 0),
                'lang' => $lang_id, 'archived' => (int)($note['is_archived'] ?? 0), 'pinned' => (int)($note['is_pinned'] ?? 0),
                'locked' => (int)($note['is_locked'] ?? 0), 'created' => $note['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $new_id = $conn->lastInsertId();
            $noteMap[$note['id']] = $new_id;
            if (!empty($note['tags'])) {
                $stmtNT = $conn->prepare("INSERT INTO note_tags (note_id, tag_id) VALUES (:nid, :tid)");
                foreach ($note['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) $stmtNT->execute(['nid' => $new_id, 'tid' => $tagMap[$old_tag_id]]);
                }
            }
        }
    }

    if (!empty($data['todos'])) {
        foreach ($data['todos'] as $todo) {
            $sql = "INSERT INTO todos (text, is_archived, sort_order, is_pinned, deadline, deadline_time, is_locked, note, created_at) 
                    VALUES (:text, :archived, :sort, :pinned, :deadline, :deadline_time, :locked, :note, :created)";
            $conn->prepare($sql)->execute([
                'text' => $todo['text'], 'archived' => (int)($todo['is_archived'] ?? 0), 'sort' => (int)($todo['sort_order'] ?? 0),
                'pinned' => (int)($todo['is_pinned'] ?? 0), 'deadline' => $todo['deadline'] ?? null, 'deadline_time' => $todo['deadline_time'] ?? null,
                'locked' => (int)($todo['is_locked'] ?? 0), 'note' => $todo['note'] ?? null, 'created' => $todo['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $new_id = $conn->lastInsertId();
            $todoMap[$todo['id']] = $new_id;
            if (!empty($todo['tags'])) {
                $stmtTT = $conn->prepare("INSERT INTO todo_tags (todo_id, tag_id) VALUES (:tid, :tagid)");
                foreach ($todo['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) $stmtTT->execute(['tid' => $new_id, 'tagid' => $tagMap[$old_tag_id]]);
                }
            }
        }
        foreach ($data['todos'] as $todo) {
            if (!empty($todo['parent_id']) && isset($todoMap[$todo['parent_id']]) && isset($todoMap[$todo['id']])) {
                $conn->prepare("UPDATE todos SET parent_id = :parent WHERE id = :id")
                     ->execute(['parent' => $todoMap[$todo['parent_id']], 'id' => $todoMap[$todo['id']]]);
            }
        }
    }

    if (!empty($data['scratchpads'])) {
        foreach ($data['scratchpads'] as $pad) {
            $type = $pad['type'] ?? 'code';
            $stmt = $conn->prepare("SELECT id FROM scratchpads WHERE name = :name AND type = :type");
            $stmt->execute(['name' => $pad['name'], 'type' => $type]);
            if ($row = $stmt->fetch()) {
                $conn->prepare("UPDATE scratchpads SET content = :content WHERE id = :id")
                     ->execute(['content' => $pad['content'], 'id' => $row['id']]);
                $scratchpadMap[$pad['id']] = $row['id'];
            } else {
                $conn->prepare("INSERT INTO scratchpads (name, content, type) VALUES (:name, :content, :type)")
                     ->execute(['name' => $pad['name'], 'content' => $pad['content'], 'type' => $type]);
                $scratchpadMap[$pad['id']] = $conn->lastInsertId();
            }
        }
    }

    if (!empty($data['inbox_items'])) {
        foreach ($data['inbox_items'] as $item) {
            $type = $item['target_type'] ?? 'unknown';
            $imported = (int)($item['is_imported'] ?? 0);
            $target_id = null;
            if ($imported && !empty($item['target_id'])) {
                if ($type === 'snippet') $target_id = $snippetMap[$item['target_id']] ?? null;
                elseif ($type === 'note') $target_id = $noteMap[$item['target_id']] ?? null;
                elseif ($type === 'todo') $target_id = $todoMap[$item['target_id']] ?? null;
                elseif ($type === 'draft') $target_id = $scratchpadMap[$item['target_id']] ?? null;
            }

            if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
                $sql = "INSERT INTO inbox_items (mail_uid, content_hash, from_email, subject, content, target_type, target_id, is_imported, is_seen, created_at) 
                        VALUES (:uid, :hash, :from, :sub, :content, :type, :tid, :imp, :seen, :created)
                        ON CONFLICT(mail_uid) DO UPDATE SET content_hash = EXCLUDED.content_hash";
            } else {
                $sql = "INSERT INTO inbox_items (mail_uid, content_hash, from_email, subject, content, target_type, target_id, is_imported, is_seen, created_at) 
                        VALUES (:uid, :hash, :from, :sub, :content, :type, :tid, :imp, :seen, :created)
                        ON DUPLICATE KEY UPDATE content_hash = :hash2";
            }
            $stmt = $conn->prepare($sql);
            $params = [
                'uid' => $item['mail_uid'] ?? null, 'hash' => $item['content_hash'], 'from' => $item['from_email'],
                'sub' => $item['subject'], 'content' => $item['content'], 'type' => $type, 'tid' => $target_id,
                'imp' => $imported, 'seen' => (int)($item['is_seen'] ?? 0), 'created' => $item['created_at'] ?? date('Y-m-d H:i:s')
            ];
            if (defined('DB_TYPE') && DB_TYPE !== 'sqlite') $params['hash2'] = $item['content_hash'];
            $stmt->execute($params);
        }
    }
    return true;
}


function getAllScratchpads($type = 'code') {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM scratchpads WHERE type = :type ORDER BY id ASC");
    $stmt->execute(['type' => $type]);
    return $stmt->fetchAll();
}

function getScratchpad($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM scratchpads WHERE id = :id");
    $stmt->execute(['id' => (int)$id]);
    return $stmt->fetch();
}

function getScratchpadContent($id = null, $type = 'code') {
    global $conn;
    if ($id === null) {
        $stmt = $conn->prepare("SELECT content FROM scratchpads WHERE type = :type ORDER BY id ASC LIMIT 1");
        $stmt->execute(['type' => $type]);
    } else {
        $stmt = $conn->prepare("SELECT content FROM scratchpads WHERE id = :id");
        $stmt->execute(['id' => (int)$id]);
    }
    $row = $stmt->fetch();
    return $row ? $row['content'] : '';
}

function saveScratchpadContent($content, $id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE scratchpads SET content = :content WHERE id = :id");
    return $stmt->execute(['content' => $content, 'id' => (int)$id]);
}

function createScratchpad($name = 'Nový draft', $type = 'code') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO scratchpads (name, content, type) VALUES (:name, '', :type)");
    $stmt->execute(['name' => $name, 'type' => $type]);
    return $conn->lastInsertId();
}

function deleteScratchpad($id) {
    global $conn;
    $id = (int)$id;
    $pad = getScratchpad($id);
    if (!$pad) return false;
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM scratchpads WHERE type = :type");
    $stmtCount->execute(['type' => $pad['type']]);
    if ($stmtCount->fetchColumn() <= 1) return false;

    $stmt = $conn->prepare("DELETE FROM scratchpads WHERE id = :id");
    return $stmt->execute(['id' => $id]);
}

function renameScratchpad($id, $name) {
    global $conn;
    $stmt = $conn->prepare("UPDATE scratchpads SET name = :name WHERE id = :id");
    return $stmt->execute(['name' => $name, 'id' => (int)$id]);
}


// INBOX FUNCTIONS
function getAllInboxItems() {
    global $conn;
    return $conn->query("SELECT * FROM inbox_items ORDER BY created_at DESC")->fetchAll();
}

function getNewInboxItems($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM inbox_items WHERE is_seen = 0 ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function deleteInboxItem($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM inbox_items WHERE id = :id");
    return $stmt->execute(['id' => (int)$id]);
}

function clearInbox() {
    global $conn;
    return $conn->query("DELETE FROM inbox_items");
}


function processInboxMail($uid, $from, $subject, $body) {
    global $conn;
    $content_hash = md5(trim($from) . trim($subject) . trim($body));
    $trusted = getSetting('inbox_trusted_emails', '');
    if (!empty($trusted)) {
        $trusted_array = array_map('trim', explode(',', strtolower($trusted)));
        if (!in_array(strtolower($from), $trusted_array)) return false;
    }

    $stmtUid = $conn->prepare("SELECT id FROM inbox_items WHERE mail_uid = :uid");
    $stmtUid->execute(['uid' => $uid]);
    if ($stmtUid->fetch()) return false;

    $stmtHash = $conn->prepare("SELECT id FROM inbox_items WHERE content_hash = :hash");
    $stmtHash->execute(['hash' => $content_hash]);
    if ($stmtHash->fetch()) return false;

    $target_type = 'unknown'; $target_id = null; $title = $subject;

    if (stripos($subject, '@note') !== false) {
        $target_type = 'note';
        $title = trim(str_ireplace('@note', '', $subject));
        $extracted = extractDateTimeFromText($title);
        $content_html = (strip_tags($body) === $body) ? nl2br(htmlspecialchars($body)) : $body;
        $target_id = saveNote($extracted['text'], $content_html);
    } elseif (stripos($subject, '@todo') !== false) {
        $target_type = 'todo';
        $title = trim(str_ireplace('@todo', '', $subject));
        $extracted = extractDateTimeFromText($title);
        $deadline = $extracted['date'];
        $deadline_time = $extracted['time'];
        if ($deadline_time && !$deadline) $deadline = date('Y-m-d');
        $target_id = saveTodo($extracted['text'], [], null, 0, $deadline, $deadline_time);
        if ($target_id) {
            $conn->prepare("UPDATE todos SET note = :note WHERE id = :id")->execute(['note' => $body, 'id' => $target_id]);
        }
    } elseif (stripos($subject, '@draft') !== false) {
        $target_type = 'draft';
        $title = trim(str_ireplace('@draft', '', $subject));
        $extracted = extractDateTimeFromText($title);
        $target_id = createScratchpad($extracted['text'], 'note');
        if ($target_id) saveScratchpadContent($body, $target_id);
    }

    $tagNames = extractHashtags($subject);
    if (!empty($tagNames) && $target_id && ($target_type == 'note' || $target_type == 'todo')) {
        $tagIds = getOrCreateTagsByNames($tagNames, $target_type);
        $tbl = ($target_type == 'note') ? 'note_tags' : 'todo_tags';
        $col = ($target_type == 'note') ? 'note_id' : 'todo_id';
        foreach ($tagIds as $tid) {
            if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
                $conn->prepare("INSERT OR IGNORE INTO $tbl ($col, tag_id) VALUES (:id, :tid)")->execute(['id' => $target_id, 'tid' => $tid]);
            } else {
                $conn->prepare("INSERT IGNORE INTO $tbl ($col, tag_id) VALUES (:id, :tid)")->execute(['id' => $target_id, 'tid' => $tid]);
            }
        }
    }

    $sql = "INSERT INTO inbox_items (mail_uid, content_hash, from_email, subject, content, target_type, target_id, is_imported, is_seen) 
            VALUES (:uid, :hash, :from, :sub, :body, :type, :tid, :imp, 0)";
    return $conn->prepare($sql)->execute([
        'uid' => $uid, 'hash' => $content_hash, 'from' => $from, 'sub' => $subject, 'body' => $body,
        'type' => $target_type, 'tid' => $target_id, 'imp' => $target_id ? 1 : 0
    ]);
}


function extractHashtags($text) {
    preg_match_all('/#(\w+)/u', $text, $matches);
    return !empty($matches[1]) ? array_map('mb_strtolower', $matches[1]) : [];
}

function getOrCreateTagsByNames($names, $type) {
    global $conn;
    $tagIds = [];
    foreach ($names as $name) {
        $stmt = $conn->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(:name) AND type = :type LIMIT 1");
        $stmt->execute(['name' => $name, 'type' => $type]);
        if ($row = $stmt->fetch()) {
            $tagIds[] = $row['id'];
        } else {
            $conn->prepare("INSERT INTO tags (name, type) VALUES (:name, :type)")->execute(['name' => $name, 'type' => $type]);
            $tagIds[] = $conn->lastInsertId();
        }
    }
    return $tagIds;
}


/**
 * Extrahuje datum a čas z textu a vrátí vyčištěný text.
 * Podporuje: d.m.Y, d.m., Y-m-d a čas H:i
 */
function extractDateTimeFromText($text) {
    $date = null;
    $time = null;

    // 1. Čas (HH:MM) - musí to být aspoň dvě cifry po dvojtečce
    if (preg_match('/\b(\d{1,2}:\d{2})\b/', $text, $matches)) {
        $time = $matches[1];
        $text = str_replace($matches[0], '', $text);
    }

    // 2. Datum (ISO: YYYY-MM-DD)
    if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $matches)) {
        $date = $matches[1];
        $text = str_replace($matches[0], '', $text);
    } 
    // 3. Datum (CZ: DD.MM.YYYY)
    elseif (preg_match('/\b(\d{1,2}\.\d{1,2}\.(\d{4}))\b/', $text, $matches)) {
        $parts = explode('.', $matches[1]);
        $date = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
        $text = str_replace($matches[0], '', $text);
    }
    // 4. Datum (CZ zkrácené: DD.MM.) - doplní aktuální rok
    elseif (preg_match('/\b(\d{1,2}\.\d{1,2}\.)\b/', $text, $matches)) {
        $parts = explode('.', rtrim($matches[1], '.'));
        $date = sprintf('%04d-%02d-%02d', date('Y'), $parts[1], $parts[0]);
        $text = str_replace($matches[0], '', $text);
    }

    // Odstranění hashtagů z textu
    $text = preg_replace('/#\w+/u', '', $text);

    return [
        'date' => $date,
        'time' => $time,
        'text' => trim(preg_replace('/\s+/', ' ', $text))
    ];
}

function importIntoItemFromInbox($id, $target_type) {
    global $conn;
    $id = (int)$id;
    $stmt = $conn->prepare("SELECT subject, content FROM inbox_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$item = $stmt->fetch()) return false;

    $target_id = null;
    if ($target_type == 'note') {
        $extracted = extractDateTimeFromText($item['subject']);
        $content_html = (strip_tags($item['content']) === $item['content']) ? nl2br(htmlspecialchars($item['content'])) : $item['content'];
        $target_id = saveNote($extracted['text'], $content_html);
    } elseif ($target_type == 'todo') {
        $extracted = extractDateTimeFromText($item['subject']);
        $deadline = $extracted['date'];
        $deadline_time = $extracted['time'];
        if ($deadline_time && !$deadline) $deadline = date('Y-m-d');
        $target_id = saveTodo($extracted['text'], [], null, 0, $deadline, $deadline_time);
        if ($target_id) {
            $conn->prepare("UPDATE todos SET note = :note WHERE id = :id")->execute(['note' => $item['content'], 'id' => $target_id]);
        }
    } elseif ($target_type == 'draft') {
        $extracted = extractDateTimeFromText($item['subject']);
        $target_id = createScratchpad($extracted['text'], 'note');
        if ($target_id) saveScratchpadContent($item['content'], $target_id);
    }

    if ($target_id) {
        $tagNames = extractHashtags($item['subject']);
        if (!empty($tagNames) && ($target_type == 'note' || $target_type == 'todo')) {
            $tagIds = getOrCreateTagsByNames($tagNames, $target_type);
            $tbl = ($target_type == 'note') ? 'note_tags' : 'todo_tags';
            $col = ($target_type == 'note') ? 'note_id' : 'todo_id';
            foreach ($tagIds as $tid) {
                if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
                    $conn->prepare("INSERT OR IGNORE INTO $tbl ($col, tag_id) VALUES (:id, :tid)")->execute(['id' => $target_id, 'tid' => $tid]);
                } else {
                    $conn->prepare("INSERT IGNORE INTO $tbl ($col, tag_id) VALUES (:id, :tid)")->execute(['id' => $target_id, 'tid' => $tid]);
                }
            }
        }
        $conn->prepare("UPDATE inbox_items SET target_type = :type, target_id = :tid, is_imported = 1 WHERE id = :id")
             ->execute(['type' => $target_type, 'tid' => $target_id, 'id' => $id]);
        return true;
    }
    return false;
}


/**
 * Vrátí seznam dostupných modelů pro daného poskytovatele.
 */
function getAvailableAiModels($provider) {
    if ($provider === 'openai') {
        return [
            'gpt-4o-mini' => 'GPT-4o Mini (Doporučeno)',
            'gpt-4o' => 'GPT-4o',
            'o1-mini' => 'o1 Mini',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-5.2' => 'GPT-5.2 Standard',
        ];
    } elseif ($provider === 'gemini') {
        return [
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite (Výchozí)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro',
            'gemini-flash-latest' => 'Gemini Flash (Aktuální verze)',
            'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro (Preview)',
            'gemini-3.1-flash-lite-preview' => 'Gemini 3.1 Flash Lite (Preview)',
            'gemini-3-pro-preview' => 'Gemini 3 Pro (Preview)',
            'gemini-3-flash-preview' => 'Gemini 3 Flash (Preview)',
        ];
    }
    return [];
}
?>
