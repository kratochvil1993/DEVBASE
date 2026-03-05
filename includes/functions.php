<?php
require_once 'db.php';

// Check if database and tables are created and up to date
// Check if database and tables are created and up to date
$current_db_version = '1.2';
$needs_init = false;

// Fast check: Try to get the version from settings
$version_res = @$conn->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
if (!$version_res || $version_res->num_rows == 0) {
    // If settings table is missing or db_version is missing, we might need init
    // But let's check if the most basic table exists to be sure
    $checkSnippets = @$conn->query("SHOW TABLES LIKE 'snippets'");
    if (!$checkSnippets || $checkSnippets->num_rows == 0) {
        $needs_init = true;
    } else {
        // Snippets exist but version doesn't - definitely need update
        $needs_init = true;
    }
} else {
    $db_version = $version_res->fetch_assoc()['setting_value'];
    if (version_compare($db_version, $current_db_version, '<')) {
        $needs_init = true;
    }
}

if ($needs_init) {
    // Determine the base URL to redirect to includes/init_db.php
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host_url = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_dir = dirname($script_name);
    
    // Normalize base_dir to always point to the root if we are in API or includes
    if (basename($base_dir) == 'api' || basename($base_dir) == 'includes') {
        $base_dir = dirname($base_dir);
    }
    
    $redirect_url = $protocol . "://" . $host_url . rtrim($base_dir, '/') . "/includes/init_db.php";
    
    header("Location: $redirect_url");
    exit;
}

function getAllSnippets($search = '') {
    global $conn;
    $sql = "SELECT s.*, l.name as language_name, l.prism_class 
            FROM snippets s 
            LEFT JOIN languages l ON s.language_id = l.id";
    
    // Column is_pinned and sort_order are now in schema.sql

    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE s.title LIKE '%$search%' OR s.code LIKE '%$search%' OR s.description LIKE '%$search%'";
    }
    
    $sql .= " ORDER BY s.is_pinned DESC, s.sort_order ASC, s.created_at DESC";
    $result = $conn->query($sql);
    
    $snippets = [];
    while ($row = $result->fetch_assoc()) {
        $row['tags'] = getSnippetTags($row['id']);
        $snippets[] = $row;
    }
    return $snippets;
}

function getSnippetTags($snippet_id) {
    global $conn;
    $snippet_id = (int)$snippet_id;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN snippet_tags st ON t.id = st.tag_id 
            WHERE st.snippet_id = $snippet_id 
            ORDER BY t.sort_order ASC, t.name ASC";
    $result = $conn->query($sql);
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

function getNoteTags($note_id) {
    global $conn;
    $note_id = (int)$note_id;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN note_tags nt ON t.id = nt.tag_id 
            WHERE nt.note_id = $note_id
            ORDER BY t.sort_order ASC, t.name ASC";
    $result = $conn->query($sql);
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

function getTodoTags($todo_id) {
    global $conn;
    $todo_id = (int)$todo_id;
    $sql = "SELECT t.id, t.name, t.color, t.sort_order FROM tags t 
            JOIN todo_tags tt ON t.id = tt.tag_id 
            WHERE tt.todo_id = $todo_id
            ORDER BY t.sort_order ASC, t.name ASC";
    $result = $conn->query($sql);
    $tags = [];
    if($result) {
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
    }
    return $tags;
}

function getAllTags($type = 'snippet') {
    global $conn;
    // Column sort_order and type are now in schema.sql

    $type = $conn->real_escape_string($type);
    $sql = "SELECT * FROM tags WHERE type = '$type' ORDER BY sort_order ASC, name ASC";
    $result = $conn->query($sql);
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

function getAllLanguages() {
    global $conn;
    $sql = "SELECT * FROM languages ORDER BY name ASC";
    $result = $conn->query($sql);
    $languages = [];
    while ($row = $result->fetch_assoc()) {
        $languages[] = $row;
    }
    return $languages;
}

function saveTag($name, $color, $type = 'snippet', $id = null) {
    global $conn;
    $name = $conn->real_escape_string($name);
    $type = $conn->real_escape_string($type);
    $color = !empty($color) ? "'" . $conn->real_escape_string($color) . "'" : "NULL";
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE tags SET name = '$name', color = $color, type = '$type' WHERE id = $id";
    } else {
        $sql = "INSERT INTO tags (name, color, type) VALUES ('$name', $color, '$type')";
    }
    return $conn->query($sql);
}

function deleteTag($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM tags WHERE id = $id";
    return $conn->query($sql);
}

function saveLanguage($name, $prism_class, $id = null) {
    global $conn;
    $name = $conn->real_escape_string($name);
    $prism_class = $conn->real_escape_string($prism_class);
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE languages SET name = '$name', prism_class = '$prism_class' WHERE id = $id";
    } else {
        $sql = "INSERT INTO languages (name, prism_class) VALUES ('$name', '$prism_class')";
    }
    return $conn->query($sql);
}

function deleteLanguage($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM languages WHERE id = $id";
    return $conn->query($sql);
}

function saveSnippet($title, $description, $code, $language_id, $tags = [], $id = null, $is_locked = 0) {
    global $conn;
    $title = $conn->real_escape_string($title);
    $description = $conn->real_escape_string($description);
    $code = $conn->real_escape_string($code);
    $language_id = $language_id ? (int)$language_id : 'NULL';
    $is_locked = (int)$is_locked;

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE snippets SET title = '$title', description = '$description', code = '$code', language_id = $language_id, is_locked = $is_locked WHERE id = $id";
    } else {
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM snippets");
        $row = $result ? $result->fetch_assoc() : null;
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO snippets (title, description, code, language_id, sort_order, is_locked) VALUES ('$title', '$description', '$code', $language_id, $next_sort, $is_locked)";
    }

    if ($conn->query($sql)) {
        $snippet_id = $id ? $id : $conn->insert_id;
        
        // Handle tags
        $conn->query("DELETE FROM snippet_tags WHERE snippet_id = $snippet_id");
        foreach ($tags as $tag_id) {
            $tag_id = (int)$tag_id;
            $conn->query("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES ($snippet_id, $tag_id)");
        }
        return $snippet_id;
    }
    return false;
}

function deleteSnippet($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM snippets WHERE id = $id";
    return $conn->query($sql);
}

function toggleSnippetPin($id) {
    global $conn;
    $id = (int)$id;
    $sql = "UPDATE snippets SET is_pinned = 1 - is_pinned WHERE id = $id";
    return $conn->query($sql);
}

function updateSnippetOrder($id, $order) {
    global $conn;
    $id = (int)$id;
    $order = (int)$order;
    $sql = "UPDATE snippets SET sort_order = $order WHERE id = $id";
    return $conn->query($sql);
}

function getAllNotes($sort = 'custom', $archive_status = 0) {
    global $conn;
    // Columns is_archived and is_pinned are now in schema.sql

    $orderBy = "n.is_pinned DESC, n.sort_order ASC, n.created_at DESC";
    
    switch ($sort) {
        case 'oldest':
            $orderBy = "n.is_pinned DESC, n.created_at ASC";
            break;
        case 'newest':
            $orderBy = "n.is_pinned DESC, n.created_at DESC";
            break;
        case 'alpha_asc':
            $orderBy = "n.is_pinned DESC, n.title ASC";
            break;
        case 'alpha_desc':
            $orderBy = "n.is_pinned DESC, n.title DESC";
            break;
        case 'custom':
            $orderBy = "n.is_pinned DESC, n.sort_order ASC, n.created_at DESC";
            break;
    }
    
    $whereClause = "";
    if ($archive_status !== 2) {
        $archive_status = (int)$archive_status;
        $whereClause = "WHERE n.is_archived = $archive_status";
    }

    $sql = "SELECT n.*, l.name as language_name, l.prism_class 
            FROM notes n
            LEFT JOIN languages l ON n.language_id = l.id
            $whereClause
            ORDER BY $orderBy";
    $result = $conn->query($sql);
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $row['tags'] = getNoteTags($row['id']);
        $notes[] = $row;
    }
    return $notes;
}

function getNote($id) {
    global $conn;
    $id = (int)$id;
    $sql = "SELECT n.*, l.name as language_name, l.prism_class 
            FROM notes n
            LEFT JOIN languages l ON n.language_id = l.id
            WHERE n.id = $id";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $row['tags'] = getNoteTags($row['id']);
        return $row;
    }
    return null;
}

function archiveNote($id, $status = 1) {
    global $conn;
    $id = (int)$id;
    $status = (int)$status;
    $sql = "UPDATE notes SET is_archived = $status WHERE id = $id";
    return $conn->query($sql);
}

function toggleNotePin($id) {
    global $conn;
    $id = (int)$id;
    $sql = "UPDATE notes SET is_pinned = 1 - is_pinned WHERE id = $id";
    return $conn->query($sql);
}

function saveNote($title, $content, $language_id = null, $tags = [], $id = null, $is_locked = 0) {
    global $conn;
    $title = $conn->real_escape_string($title);
    $content = $conn->real_escape_string($content);
    $language_id = $language_id ? (int)$language_id : 'NULL';
    $is_locked = (int)$is_locked;

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE notes SET title = '$title', content = '$content', language_id = $language_id, is_locked = $is_locked WHERE id = $id";
    } else {
        // Get min sort_order
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM notes");
        $row = $result->fetch_assoc();
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO notes (title, content, sort_order, language_id, is_locked) VALUES ('$title', '$content', $next_sort, $language_id, $is_locked)";
    }

    if ($conn->query($sql)) {
        $note_id = $id ? $id : $conn->insert_id;
        
        // Handle tags
        $conn->query("DELETE FROM note_tags WHERE note_id = $note_id");
        foreach ($tags as $tag_id) {
            $tag_id = (int)$tag_id;
            $conn->query("INSERT INTO note_tags (note_id, tag_id) VALUES ($note_id, $tag_id)");
        }
        return $note_id;
    }
    return false;
}

function updateNoteOrder($id, $order) {
    global $conn;
    $id = (int)$id;
    $order = (int)$order;
    $sql = "UPDATE notes SET sort_order = $order WHERE id = $id";
    return $conn->query($sql);
}

function deleteNote($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM notes WHERE id = $id";
    return $conn->query($sql);
}

function getSetting($key, $default = null) {
    global $conn;
    $key = $conn->real_escape_string($key);
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = '$key'");
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

function updateSetting($key, $value) {
    global $conn;
    $key = $conn->real_escape_string($key);
    $value = $conn->real_escape_string($value);
    return $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_value = '$value'");
}

// Table structure is now handled by schema.sql


function getAllTodos($archive_status = 0) {
    global $conn;
    $archive_status = (int)$archive_status;

    $sql = "SELECT * FROM todos WHERE is_archived = $archive_status ORDER BY is_pinned DESC, sort_order ASC, created_at DESC";
    $result = $conn->query($sql);
    $todos = [];
    if($result) {
        while ($row = $result->fetch_assoc()) {
            $row['tags'] = getTodoTags($row['id']);
            $todos[] = $row;
        }
    }
    return $todos;
}

function saveTodo($text, $tags = [], $id = null, $is_locked = 0, $deadline = null, $deadline_time = null, $note = null) {
    global $conn;
    $text = $conn->real_escape_string($text);
    $is_locked = (int)$is_locked;

    if ($deadline === null && !empty($_POST['deadline'])) {
        $deadline = $_POST['deadline'];
    }
    
    if ($deadline_time === null && !empty($_POST['deadline_time'])) {
        $deadline_time = $_POST['deadline_time'];
    }
    
    if ($note === null && isset($_POST['note'])) {
        $note = $_POST['note'];
    }
    
    $deadline_val = !empty($deadline) ? "'" . $conn->real_escape_string($deadline) . "'" : "NULL";
    $deadline_time_val = !empty($deadline_time) ? "'" . $conn->real_escape_string($deadline_time) . "'" : "NULL";
    $note_val = ($note !== null && $note !== '') ? "'" . $conn->real_escape_string($note) . "'" : "NULL";
    
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE todos SET text = '$text', deadline = $deadline_val, deadline_time = $deadline_time_val, note = $note_val, is_locked = $is_locked WHERE id = $id";
    } else {
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM todos");
        $row = $result ? $result->fetch_assoc() : null;
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO todos (text, deadline, deadline_time, note, sort_order, is_locked) VALUES ('$text', $deadline_val, $deadline_time_val, $note_val, $next_sort, $is_locked)";
    }
    
    if ($conn->query($sql)) {
        $todo_id = $id ? $id : $conn->insert_id;
        
        $conn->query("DELETE FROM todo_tags WHERE todo_id = $todo_id");
        if (is_array($tags)) {
            foreach ($tags as $tag_id) {
                $tag_id = (int)$tag_id;
                $conn->query("INSERT INTO todo_tags (todo_id, tag_id) VALUES ($todo_id, $tag_id)");
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
    $sql = "UPDATE todos SET is_archived = $status WHERE id = $id";
    return $conn->query($sql);
}

function toggleTodoPin($id) {
    global $conn;
    $id = (int)$id;
    $sql = "UPDATE todos SET is_pinned = 1 - is_pinned WHERE id = $id";
    return $conn->query($sql);
}

function updateTodoOrder($id, $order) {
    global $conn;
    $id = (int)$id;
    $order = (int)$order;
    $sql = "UPDATE todos SET sort_order = $order WHERE id = $id";
    return $conn->query($sql);
}

function deleteTodo($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM todos WHERE id = $id";
    return $conn->query($sql);
}

function getGlobalStats() {
    global $conn;
    
    $stats = [
        'total_snippets' => 0,
        'total_notes' => 0,
        'total_todos' => 0,
        'total_code_drafts' => 0,
        'total_note_drafts' => 0,
        'last_added' => null,
        'top_tags' => []
    ];
    
    // Total Snippets
    $res = $conn->query("SELECT COUNT(*) as count FROM snippets");
    if ($res) $stats['total_snippets'] = $res->fetch_assoc()['count'];
    
    // Total Notes (active)
    $res = $conn->query("SELECT COUNT(*) as count FROM notes WHERE is_archived = 0");
    if ($res) $stats['total_notes'] = $res->fetch_assoc()['count'];

    // Total Todos (active)
    $res = $conn->query("SELECT COUNT(*) as count FROM todos WHERE is_archived = 0");
    if ($res) $stats['total_todos'] = $res->fetch_assoc()['count'];

    // Total Drafts (Scratchpads)
    $res = $conn->query("SELECT COUNT(*) as count FROM scratchpads WHERE type = 'code'");
    if ($res) $stats['total_code_drafts'] = $res->fetch_assoc()['count'];
    
    $res = $conn->query("SELECT COUNT(*) as count FROM scratchpads WHERE type = 'note'");
    if ($res) $stats['total_note_drafts'] = $res->fetch_assoc()['count'];
    
    // Last Added
    $res = $conn->query("SELECT title, created_at FROM (
        SELECT title, created_at FROM snippets
        UNION ALL
        SELECT title, created_at FROM notes
    ) as combined ORDER BY created_at DESC LIMIT 1");
    if ($res) $stats['last_added'] = $res->fetch_assoc();
    
    // Top Tags
    $res = $conn->query("SELECT t.name, t.color, (
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
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $stats['top_tags'][] = $row;
        }
    }
    
    // Total Inbox (unseen)
    $res = $conn->query("SELECT COUNT(*) as count FROM inbox_items WHERE is_seen = 0");
    if ($res) $stats['total_inbox_new'] = (int)$res->fetch_assoc()['count'];

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
            AND deadline <= '$tomorrow' 
            ORDER BY deadline ASC, deadline_time ASC";
    
    $result = $conn->query($sql);
    $reminders = [
        'critical' => [], // past or today (already passed time)
        'warning' => []   // today (future time) or tomorrow
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['deadline'] < $today) {
                // Completely in the past
                $reminders['critical'][] = $row;
            } elseif ($row['deadline'] == $today) {
                // Today - check time
                if ($row['deadline_time'] !== null && $row['deadline_time'] > $now_time) {
                    // It's today but in the future
                    $reminders['warning'][] = $row;
                } else {
                    // It's today and time passed (or no time set, which implies today generally)
                    $reminders['critical'][] = $row;
                }
            } elseif ($row['deadline'] == $tomorrow) {
                // Tomorrow
                $reminders['warning'][] = $row;
            }
        }
    }
    
    return $reminders;
}

function isAppLocked() {
    $security_enabled = getSetting('security_enabled', '0');
    if ($security_enabled !== '1') {
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

function verifyAppPassword($password) {
    if (empty($password)) return false;
    $hashed_password = getSetting('app_password');
    if (!$hashed_password) return false;
    
    if (password_verify($password, $hashed_password)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['app_unlocked'] = true;
        return true;
    }
    return false;
}

/**
 * Zformátuje všechny aktivní úkoly pro AI analýzu.
 */
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
    if (!$result) return "Žádné aktivní úkoly nebyly nalezeny.";
    
    $output = "SOUČASNÉ DATUM: " . $today . "\n\nNEVYŘÍZENÉ ÚKOLY:\n";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
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
    } else {
        $output .= "Momentálně nemáš žádné aktivní úkoly. Skvělá práce!";
    }
    
    return $output;
}

function exportAllData() {
    global $conn;
    
    $data = [
        'version' => '1.0',
        'export_date' => date('c'),
        'settings' => [],
        'languages' => [],
        'tags' => [],
        'snippets' => [],
        'notes' => [],
        'todos' => [],
        'scratchpads' => [],
        'inbox_items' => []
    ];

    // Settings
    $res = $conn->query("SELECT * FROM settings");
    while ($row = $res->fetch_assoc()) {
        $data['settings'][$row['setting_key']] = $row['setting_value'];
    }

    // Languages
    $res = $conn->query("SELECT * FROM languages");
    while ($row = $res->fetch_assoc()) {
        $data['languages'][] = $row;
    }

    // Tags
    $res = $conn->query("SELECT * FROM tags");
    while ($row = $res->fetch_assoc()) {
        $data['tags'][] = $row;
    }

    // Snippets with tags
    $res = $conn->query("SELECT * FROM snippets");
    while ($row = $res->fetch_assoc()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM snippet_tags WHERE snippet_id = " . $row['id']);
        while ($t = $tags_res->fetch_assoc()) {
            $row['tags'][] = $t['tag_id'];
        }
        $data['snippets'][] = $row;
    }

    // Notes with tags
    $res = $conn->query("SELECT * FROM notes");
    while ($row = $res->fetch_assoc()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM note_tags WHERE note_id = " . $row['id']);
        while ($t = $tags_res->fetch_assoc()) {
            $row['tags'][] = $t['tag_id'];
        }
        $data['notes'][] = $row;
    }

    // Todos with tags
    $res = $conn->query("SELECT * FROM todos");
    while ($row = $res->fetch_assoc()) {
        $row['tags'] = [];
        $tags_res = $conn->query("SELECT tag_id FROM todo_tags WHERE todo_id = " . $row['id']);
        while ($t = $tags_res->fetch_assoc()) {
            $row['tags'][] = $t['tag_id'];
        }
        $data['todos'][] = $row;
    }

    // Inbox Items
    $res = $conn->query("SELECT * FROM inbox_items");
    while ($row = $res->fetch_assoc()) {
        $data['inbox_items'][] = $row;
    }

    return $data;
}

function importAllData($data, $mode = 'append') {
    global $conn;

    if ($mode === 'overwrite') {
        $conn->query("DELETE FROM snippet_tags");
        $conn->query("DELETE FROM note_tags");
        $conn->query("DELETE FROM todo_tags");
        $conn->query("DELETE FROM snippets");
        $conn->query("DELETE FROM notes");
        $conn->query("DELETE FROM todos");
        $conn->query("DELETE FROM tags");
        $conn->query("DELETE FROM languages");
        $conn->query("DELETE FROM scratchpads");
        $conn->query("DELETE FROM inbox_items");
        // We don't delete all settings to avoid breaking security unless user wants that
        // But we could overwrite them.
    }

    // Import Settings
    if (!empty($data['settings'])) {
        foreach ($data['settings'] as $key => $value) {
            updateSetting($key, $value);
        }
    }

    // Helper to map old IDs to new IDs
    $langMap = [];
    $tagMap = [];
    $snippetMap = [];
    $noteMap = [];
    $todoMap = [];
    $scratchpadMap = [];

    // Import Languages
    if (!empty($data['languages'])) {
        foreach ($data['languages'] as $lang) {
            $name = $conn->real_escape_string($lang['name']);
            $prism = $conn->real_escape_string($lang['prism_class']);
            
            $check = $conn->query("SELECT id FROM languages WHERE name = '$name'");
            if ($row = $check->fetch_assoc()) {
                $langMap[$lang['id']] = $row['id'];
            } else {
                $conn->query("INSERT INTO languages (name, prism_class) VALUES ('$name', '$prism')");
                $langMap[$lang['id']] = $conn->insert_id;
            }
        }
    }

    // Import Tags
    if (!empty($data['tags'])) {
        foreach ($data['tags'] as $tag) {
            $name = $conn->real_escape_string($tag['name']);
            $color = !empty($tag['color']) ? "'" . $conn->real_escape_string($tag['color']) . "'" : "NULL";
            $type = $conn->real_escape_string($tag['type'] ?? 'snippet');
            $sort = (int)($tag['sort_order'] ?? 0);

            $check = $conn->query("SELECT id FROM tags WHERE name = '$name' AND type = '$type'");
            if ($row = $check->fetch_assoc()) {
                $tagMap[$tag['id']] = $row['id'];
            } else {
                $conn->query("INSERT INTO tags (name, color, type, sort_order) VALUES ('$name', $color, '$type', $sort)");
                $tagMap[$tag['id']] = $conn->insert_id;
            }
        }
    }

    // Import Snippets
    if (!empty($data['snippets'])) {
        foreach ($data['snippets'] as $snip) {
            $title = $conn->real_escape_string($snip['title']);
            $desc = $conn->real_escape_string($snip['description']);
            $code = $conn->real_escape_string($snip['code']);
            $lang_id = isset($langMap[$snip['language_id']]) ? $langMap[$snip['language_id']] : 'NULL';
            $pinned = (int)($snip['is_pinned'] ?? 0);
            $locked = (int)($snip['is_locked'] ?? 0);
            $sort = (int)($snip['sort_order'] ?? 0);
            $created = !empty($snip['created_at']) ? "'" . $conn->real_escape_string($snip['created_at']) . "'" : "CURRENT_TIMESTAMP";
            
            $conn->query("INSERT INTO snippets (title, description, code, language_id, is_pinned, is_locked, sort_order, created_at) VALUES ('$title', '$desc', '$code', $lang_id, $pinned, $locked, $sort, $created)");
            $new_id = $conn->insert_id;
            $snippetMap[$snip['id']] = $new_id;

            if (!empty($snip['tags'])) {
                foreach ($snip['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) {
                        $new_tag_id = $tagMap[$old_tag_id];
                        $conn->query("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES ($new_id, $new_tag_id)");
                    }
                }
            }
        }
    }

    // Import Notes
    if (!empty($data['notes'])) {
        foreach ($data['notes'] as $note) {
            $title = $conn->real_escape_string($note['title']);
            $content = $conn->real_escape_string($note['content']);
            $sort = (int)($note['sort_order'] ?? 0);
            $archived = (int)($note['is_archived'] ?? 0);
            $pinned = (int)($note['is_pinned'] ?? 0);
            $locked = (int)($note['is_locked'] ?? 0);
            $lang_id = isset($langMap[$note['language_id']]) ? $langMap[$note['language_id']] : 'NULL';
            $created = !empty($note['created_at']) ? "'" . $conn->real_escape_string($note['created_at']) . "'" : "CURRENT_TIMESTAMP";

            $conn->query("INSERT INTO notes (title, content, sort_order, language_id, is_archived, is_pinned, is_locked, created_at) VALUES ('$title', '$content', $sort, $lang_id, $archived, $pinned, $locked, $created)");
            $new_id = $conn->insert_id;
            $noteMap[$note['id']] = $new_id;

            if (!empty($note['tags'])) {
                foreach ($note['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) {
                        $new_tag_id = $tagMap[$old_tag_id];
                        $conn->query("INSERT INTO note_tags (note_id, tag_id) VALUES ($new_id, $new_tag_id)");
                    }
                }
            }
        }
    }

    // Import Todos
    if (!empty($data['todos'])) {
        foreach ($data['todos'] as $todo) {
            $text = $conn->real_escape_string($todo['text']);
            $archived = (int)($todo['is_archived'] ?? 0);
            $pinned = (int)($todo['is_pinned'] ?? 0);
            $sort = (int)($todo['sort_order'] ?? 0);
            $locked = (int)($todo['is_locked'] ?? 0);
            $deadline = !empty($todo['deadline']) ? "'" . $conn->real_escape_string($todo['deadline']) . "'" : "NULL";
            $note = !empty($todo['note']) ? "'" . $conn->real_escape_string($todo['note']) . "'" : "NULL";
            $created = !empty($todo['created_at']) ? "'" . $conn->real_escape_string($todo['created_at']) . "'" : "CURRENT_TIMESTAMP";

            $conn->query("INSERT INTO todos (text, is_archived, sort_order, is_pinned, deadline, is_locked, note, created_at) VALUES ('$text', $archived, $sort, $pinned, $deadline, $locked, $note, $created)");
            $new_id = $conn->insert_id;
            $todoMap[$todo['id']] = $new_id;

            if (!empty($todo['tags'])) {
                foreach ($todo['tags'] as $old_tag_id) {
                    if (isset($tagMap[$old_tag_id])) {
                        $new_tag_id = $tagMap[$old_tag_id];
                        $conn->query("INSERT INTO todo_tags (todo_id, tag_id) VALUES ($new_id, $new_tag_id)");
                    }
                }
            }
        }
    }

    // Import Scratchpads
    if (!empty($data['scratchpads'])) {
        foreach ($data['scratchpads'] as $pad) {
            $name = $conn->real_escape_string($pad['name']);
            $content = $conn->real_escape_string($pad['content']);
            $type = $conn->real_escape_string($pad['type'] ?? 'code');
            
            $check = $conn->query("SELECT id FROM scratchpads WHERE name = '$name' AND type = '$type'");
            if ($row = $check->fetch_assoc()) {
                $conn->query("UPDATE scratchpads SET content = '$content' WHERE id = " . $row['id']);
                $scratchpadMap[$pad['id']] = $row['id'];
            } else {
                $conn->query("INSERT INTO scratchpads (name, content, type) VALUES ('$name', '$content', '$type')");
                $scratchpadMap[$pad['id']] = $conn->insert_id;
            }
        }
    }

    // Import Inbox Items
    if (!empty($data['inbox_items'])) {
        foreach ($data['inbox_items'] as $item) {
            $uid = !empty($item['mail_uid']) ? "'" . $conn->real_escape_string($item['mail_uid']) . "'" : "NULL";
            $hash = $conn->real_escape_string($item['content_hash']);
            $subject = $conn->real_escape_string($item['subject']);
            $content = $conn->real_escape_string($item['content']);
            $from = $conn->real_escape_string($item['from_email']);
            $type = $conn->real_escape_string($item['target_type'] ?? 'unknown');
            $seen = (int)($item['is_seen'] ?? 0);
            $imported = (int)($item['is_imported'] ?? 0);
            $created = !empty($item['created_at']) ? "'" . $conn->real_escape_string($item['created_at']) . "'" : "CURRENT_TIMESTAMP";
            
            $target_id = 'NULL';
            if ($imported && !empty($item['target_id'])) {
                if ($type === 'snippet' && isset($snippetMap[$item['target_id']])) $target_id = $snippetMap[$item['target_id']];
                elseif ($type === 'note' && isset($noteMap[$item['target_id']])) $target_id = $noteMap[$item['target_id']];
                elseif ($type === 'todo' && isset($todoMap[$item['target_id']])) $target_id = $todoMap[$item['target_id']];
                elseif ($type === 'draft' && isset($scratchpadMap[$item['target_id']])) $target_id = $scratchpadMap[$item['target_id']];
            }

            $sql = "INSERT INTO inbox_items (mail_uid, content_hash, subject, content, from_email, target_type, target_id, is_imported, is_seen, created_at) 
                    VALUES ($uid, '$hash', '$subject', '$content', '$from', '$type', $target_id, $imported, $seen, $created)
                    ON DUPLICATE KEY UPDATE content_hash = '$hash'";
            $conn->query($sql);
        }
    }

    return true;
}

function getAllScratchpads($type = 'code') {
    global $conn;
    $type = $conn->real_escape_string($type);
    $result = $conn->query("SELECT * FROM scratchpads WHERE type = '$type' ORDER BY id ASC");
    $scratchpads = [];
    while ($row = $result->fetch_assoc()) {
        $scratchpads[] = $row;
    }
    return $scratchpads;
}

function getScratchpad($id) {
    global $conn;
    $id = (int)$id;
    $result = $conn->query("SELECT * FROM scratchpads WHERE id = $id");
    return $result ? $result->fetch_assoc() : null;
}

function getScratchpadContent($id = null, $type = 'code') {
    global $conn;
    if ($id === null) {
        $type = $conn->real_escape_string($type);
        $result = $conn->query("SELECT content FROM scratchpads WHERE type = '$type' ORDER BY id ASC LIMIT 1");
    } else {
        $id = (int)$id;
        $result = $conn->query("SELECT content FROM scratchpads WHERE id = $id");
    }
    if ($result && $row = $result->fetch_assoc()) {
        return $row['content'];
    }
    return '';
}

function saveScratchpadContent($content, $id) {
    global $conn;
    $id = (int)$id;
    $content = $conn->real_escape_string($content);
    return $conn->query("UPDATE scratchpads SET content = '$content' WHERE id = $id");
}

function createScratchpad($name = 'Nový draft', $type = 'code') {
    global $conn;
    $name = $conn->real_escape_string($name);
    $type = $conn->real_escape_string($type);
    $conn->query("INSERT INTO scratchpads (name, content, type) VALUES ('$name', '', '$type')");
    return $conn->insert_id;
}

function deleteScratchpad($id) {
    global $conn;
    $id = (int)$id;
    
    // Ensure we don't delete the last scratchpad of this type
    $id = (int)$id;
    $pad = getScratchpad($id);
    if (!$pad) return false;
    $type = $conn->real_escape_string($pad['type']);
    $res = $conn->query("SELECT COUNT(*) as count FROM scratchpads WHERE type = '$type'");
    $row = $res->fetch_assoc();
    if ($row['count'] <= 1) return false;

    $sql = "DELETE FROM scratchpads WHERE id = $id";
    return $conn->query($sql);
}

function renameScratchpad($id, $name) {
    global $conn;
    $id = (int)$id;
    $name = $conn->real_escape_string($name);
    return $conn->query("UPDATE scratchpads SET name = '$name' WHERE id = $id");
}

// INBOX FUNCTIONS
function getAllInboxItems() {
    global $conn;
    $sql = "SELECT * FROM inbox_items ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function getNewInboxItems($limit = 5) {
    global $conn;
    $limit = (int)$limit;
    $sql = "SELECT * FROM inbox_items WHERE is_seen = 0 ORDER BY created_at DESC LIMIT $limit";
    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function deleteInboxItem($id) {
    global $conn;
    $id = (int)$id;
    return $conn->query("DELETE FROM inbox_items WHERE id = $id");
}

function clearInbox() {
    global $conn;
    return $conn->query("DELETE FROM inbox_items");
}

function processInboxMail($uid, $from, $subject, $body) {
    global $conn;

    // Create content hash to prevent duplicates (same sender, subject and body)
    $content_hash = md5(trim($from) . trim($subject) . trim($body));

    $uid_escaped = $conn->real_escape_string($uid);
    $from_escaped = $conn->real_escape_string($from);
    $subject_escaped = $conn->real_escape_string($subject);
    $body_escaped = $conn->real_escape_string($body);

    // Check for trusted emails
    $trusted = getSetting('inbox_trusted_emails', '');
    if (!empty($trusted)) {
        $trusted_array = array_map('trim', explode(',', strtolower($trusted)));
        if (!in_array(strtolower($from), $trusted_array)) {
            return false; // Not trusted
        }
    }

    // Check if duplicate by UID
    $check_uid = $conn->query("SELECT id FROM inbox_items WHERE mail_uid = '$uid_escaped'");
    if ($check_uid && $check_uid->num_rows > 0) return false;

    // Check if duplicate by Content Hash
    $check_hash = $conn->query("SELECT id FROM inbox_items WHERE content_hash = '$content_hash'");
    if ($check_hash && $check_hash->num_rows > 0) return false;

    $target_type = 'unknown';
    $target_id = null;
    $title = $subject;

    if (stripos($subject, '@note') !== false) {
        $target_type = 'note';
        $title = trim(str_ireplace('@note', '', $subject));
        $extracted = extractDateTimeFromText($title);
        $clean_title = $extracted['text'];
        $content_html = (strip_tags($body) === $body) ? nl2br(htmlspecialchars($body)) : $body;
        $target_id = saveNote($clean_title, $content_html);
    } elseif (stripos($subject, '@todo') !== false) {
        $target_type = 'todo';
        $title = trim(str_ireplace('@todo', '', $subject));
        
        $extracted = extractDateTimeFromText($title);
        $deadline = $extracted['date'];
        $deadline_time = $extracted['time'];
        $clean_title = $extracted['text'];

        // Pokud je zadán čas, ale ne datum, předpokládáme dnešek
        if ($deadline_time && !$deadline) {
            $deadline = date('Y-m-d');
        }

        $target_id = saveTodo($clean_title, [], null, 0, $deadline, $deadline_time);
        if ($target_id) {
            $conn->query("UPDATE todos SET note = '$body_escaped' WHERE id = $target_id");
        }
    } elseif (stripos($subject, '@draft') !== false) {
        $target_type = 'draft';
        $title = trim(str_ireplace('@draft', '', $subject));
        $extracted = extractDateTimeFromText($title);
        $clean_title = $extracted['text'];
        $target_id = createScratchpad($clean_title, 'note');
        if ($target_id) {
            saveScratchpadContent($body, $target_id);
        }
    } else {
        // No tag found - keep it in Inbox for manual import
        $target_type = 'unknown';
        $target_id = null;
    }

    // Extract potential hashtags from original subject
    $tagNames = extractHashtags($subject);
    if (!empty($tagNames) && $target_id && ($target_type == 'note' || $target_type == 'todo')) {
        $tagIds = getOrCreateTagsByNames($tagNames, $target_type);
        if ($target_type == 'note') {
            foreach ($tagIds as $tid) $conn->query("INSERT IGNORE INTO note_tags (note_id, tag_id) VALUES ($target_id, $tid)");
        } else {
            foreach ($tagIds as $tid) $conn->query("INSERT IGNORE INTO todo_tags (todo_id, tag_id) VALUES ($target_id, $tid)");
        }
    }

    $is_imported = $target_id ? 1 : 0;
    $target_id_val = $target_id ?: 'NULL';
    $sql = "INSERT INTO inbox_items (mail_uid, content_hash, from_email, subject, content, target_type, target_id, is_imported, is_seen) 
            VALUES ('$uid_escaped', '$content_hash', '$from_escaped', '$subject_escaped', '$body_escaped', '$target_type', $target_id_val, $is_imported, 0)";
    return $conn->query($sql);
}

function extractHashtags($text) {
    preg_match_all('/#(\w+)/u', $text, $matches);
    return !empty($matches[1]) ? array_map('mb_strtolower', $matches[1]) : [];
}

function getOrCreateTagsByNames($names, $type) {
    global $conn;
    $tagIds = [];
    foreach ($names as $name) {
        $name = $conn->real_escape_string($name);
        $type = $conn->real_escape_string($type);
        $check = $conn->query("SELECT id FROM tags WHERE LOWER(name) = LOWER('$name') AND type = '$type' LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            $tagIds[] = $row['id'];
        } else {
            // Create new tag
            if ($conn->query("INSERT INTO tags (name, type) VALUES ('$name', '$type')")) {
                $tagIds[] = $conn->insert_id;
            }
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
    $checkRes = $conn->query("SELECT subject, content FROM inbox_items WHERE id = $id");
    $item = $checkRes ? $checkRes->fetch_assoc() : null;
    if (!$item) return false;

    $subject = $conn->real_escape_string($item['subject']);
    $body = $conn->real_escape_string($item['content']);
    $target_id = null;

    if ($target_type == 'note') {
        $extracted = extractDateTimeFromText($item['subject']);
        $content_html = (strip_tags($item['content']) === $item['content']) ? nl2br(htmlspecialchars($item['content'])) : $item['content'];
        $target_id = saveNote($extracted['text'], $content_html);
    } elseif ($target_type == 'todo') {
        $extracted = extractDateTimeFromText($item['subject']);
        $deadline = $extracted['date'];
        $deadline_time = $extracted['time'];
        
        if ($deadline_time && !$deadline) {
            $deadline = date('Y-m-d');
        }

        $target_id = saveTodo($extracted['text'], [], null, 0, $deadline, $deadline_time);
        if ($target_id) {
            $conn->query("UPDATE todos SET note = '$body' WHERE id = $target_id");
        }
    } elseif ($target_type == 'draft') {
        $extracted = extractDateTimeFromText($item['subject']);
        $target_id = createScratchpad($extracted['text'], 'note');
        if ($target_id) {
            saveScratchpadContent($item['content'], $target_id);
        }
    }

    if ($target_id) {
        $target_id_val = (int)$target_id;
        
        // Handle tags (extract from subject again if manual import)
        $tagNames = extractHashtags($item['subject']);
        if (!empty($tagNames) && ($target_type == 'note' || $target_type == 'todo')) {
            $tagIds = getOrCreateTagsByNames($tagNames, $target_type);
            if ($target_type == 'note') {
                foreach ($tagIds as $tid) $conn->query("INSERT IGNORE INTO note_tags (note_id, tag_id) VALUES ($target_id_val, $tid)");
            } else {
                foreach ($tagIds as $tid) $conn->query("INSERT IGNORE INTO todo_tags (todo_id, tag_id) VALUES ($target_id_val, $tid)");
            }
        }

        $conn->query("UPDATE inbox_items SET target_type = '$target_type', target_id = $target_id_val, is_imported = 1 WHERE id = $id");
        return true;
    }
    return false;
}
?>
