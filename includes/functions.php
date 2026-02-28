<?php
require_once 'db.php';

// Check if database and tables are created and up to date
$checkSettings = @$conn->query("SHOW TABLES LIKE 'settings'");
$checkSnippets = @$conn->query("SHOW TABLES LIKE 'snippets'");
$checkScratchpads = @$conn->query("SHOW TABLES LIKE 'scratchpads'");

// Important columns for migrations
$checkSnippetCols = @$conn->query("SHOW COLUMNS FROM snippets LIKE 'is_locked'");
$checkSnippetPinned = @$conn->query("SHOW COLUMNS FROM snippets LIKE 'is_pinned'");
$checkTodoCols = @$conn->query("SHOW COLUMNS FROM todos LIKE 'deadline'");
$checkTodoPinned = @$conn->query("SHOW COLUMNS FROM todos LIKE 'is_pinned'");
$checkNoteCols = @$conn->query("SHOW COLUMNS FROM notes LIKE 'language_id'");
$checkNoteArchived = @$conn->query("SHOW COLUMNS FROM notes LIKE 'is_archived'");
$checkTagsType = @$conn->query("SHOW COLUMNS FROM tags LIKE 'type'");

if (!$checkSettings || $checkSettings->num_rows == 0 ||
    !$checkSnippets || $checkSnippets->num_rows == 0 || 
    !$checkScratchpads || $checkScratchpads->num_rows == 0 || 
    !$checkSnippetCols || $checkSnippetCols->num_rows == 0 ||
    !$checkSnippetPinned || $checkSnippetPinned->num_rows == 0 ||
    !$checkTodoCols || $checkTodoCols->num_rows == 0 ||
    !$checkTodoPinned || $checkTodoPinned->num_rows == 0 ||
    !$checkNoteCols || $checkNoteCols->num_rows == 0 ||
    !$checkNoteArchived || $checkNoteArchived->num_rows == 0 ||
    !$checkTagsType || $checkTagsType->num_rows == 0) {
    
    // Determine path to includes/init_db.php
    $path = "includes/init_db.php";
    if (!file_exists($path)) {
        $path = "../includes/init_db.php";
    }
    header("Location: $path");
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

function saveTodo($text, $tags = [], $id = null, $is_locked = 0, $deadline = null) {
    global $conn;
    $text = $conn->real_escape_string($text);
    $is_locked = (int)$is_locked;

    if ($deadline === null && !empty($_POST['deadline'])) {
        $deadline = $_POST['deadline'];
    }
    
    $deadline_val = !empty($deadline) ? "'" . $conn->real_escape_string($deadline) . "'" : "NULL";
    
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE todos SET text = '$text', deadline = $deadline_val, is_locked = $is_locked WHERE id = $id";
    } else {
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM todos");
        $row = $result ? $result->fetch_assoc() : null;
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO todos (text, deadline, sort_order, is_locked) VALUES ('$text', $deadline_val, $next_sort, $is_locked)";
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
    
    return $stats;
}

function getTodoReminders() {
    global $conn;
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $sql = "SELECT * FROM todos 
            WHERE is_archived = 0 
            AND deadline IS NOT NULL 
            AND deadline <= '$tomorrow' 
            ORDER BY deadline ASC";
    
    $result = $conn->query($sql);
    $reminders = [
        'critical' => [], // today or past (red)
        'warning' => []   // tomorrow (orange)
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['deadline'] <= $today) {
                $reminders['critical'][] = $row;
            } else if ($row['deadline'] == $tomorrow) {
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
        'scratchpads' => []
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

    // Scratchpads
    $res = $conn->query("SELECT * FROM scratchpads");
    while ($row = $res->fetch_assoc()) {
        $data['scratchpads'][] = $row;
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
            
            $conn->query("INSERT INTO snippets (title, description, code, language_id, is_pinned, is_locked, sort_order) VALUES ('$title', '$desc', '$code', $lang_id, $pinned, $locked, $sort)");
            $new_id = $conn->insert_id;

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

            $conn->query("INSERT INTO notes (title, content, sort_order, language_id, is_archived, is_pinned, is_locked) VALUES ('$title', '$content', $sort, $lang_id, $archived, $pinned, $locked)");
            $new_id = $conn->insert_id;

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
            $deadline = !empty($todo['deadline']) ? "'" . $conn->real_escape_string($todo['deadline']) . "'" : "NULL";

            $conn->query("INSERT INTO todos (text, is_archived, sort_order, is_pinned, deadline) VALUES ('$text', $archived, $sort, $pinned, $deadline)");
            $new_id = $conn->insert_id;

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
            
            $check = $conn->query("SELECT id FROM scratchpads WHERE name = '$name'");
            if ($row = $check->fetch_assoc()) {
                $conn->query("UPDATE scratchpads SET content = '$content' WHERE name = '$name'");
            } else {
                $conn->query("INSERT INTO scratchpads (name, content) VALUES ('$name', '$content')");
            }
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
?>
