<?php
require_once 'db.php';

function getAllSnippets($search = '') {
    global $conn;
    $sql = "SELECT s.*, l.name as language_name, l.prism_class 
            FROM snippets s 
            LEFT JOIN languages l ON s.language_id = l.id";
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $sql .= " WHERE s.title LIKE '%$search%' OR s.code LIKE '%$search%' OR s.description LIKE '%$search%'";
    }
    
    $sql .= " ORDER BY s.created_at DESC";
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
    @$conn->query("ALTER TABLE tags ADD COLUMN sort_order INT DEFAULT 0"); // add if missing
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

function saveSnippet($title, $description, $code, $language_id, $tags = [], $id = null) {
    global $conn;
    $title = $conn->real_escape_string($title);
    $description = $conn->real_escape_string($description);
    $code = $conn->real_escape_string($code);
    $language_id = $language_id ? (int)$language_id : 'NULL';

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE snippets SET title = '$title', description = '$description', code = '$code', language_id = $language_id WHERE id = $id";
    } else {
        $sql = "INSERT INTO snippets (title, description, code, language_id) VALUES ('$title', '$description', '$code', $language_id)";
    }

    if ($conn->query($sql)) {
        $snippet_id = $id ? $id : $conn->insert_id;
        
        // Handle tags
        $conn->query("DELETE FROM snippet_tags WHERE snippet_id = $snippet_id");
        foreach ($tags as $tag_id) {
            $tag_id = (int)$tag_id;
            $conn->query("INSERT INTO snippet_tags (snippet_id, tag_id) VALUES ($snippet_id, $tag_id)");
        }
        return true;
    }
    return false;
}

function deleteSnippet($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM snippets WHERE id = $id";
    return $conn->query($sql);
}

function getAllNotes($sort = 'custom', $archive_status = 0) {
    global $conn;
    @$conn->query("ALTER TABLE notes ADD COLUMN is_archived TINYINT(1) DEFAULT 0"); // add if missing
    $orderBy = "n.sort_order ASC, n.created_at DESC";
    
    switch ($sort) {
        case 'oldest':
            $orderBy = "n.created_at ASC";
            break;
        case 'newest':
            $orderBy = "n.created_at DESC";
            break;
        case 'alpha_asc':
            $orderBy = "n.title ASC";
            break;
        case 'alpha_desc':
            $orderBy = "n.title DESC";
            break;
        case 'custom':
            $orderBy = "n.sort_order ASC, n.created_at DESC";
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

function archiveNote($id, $status = 1) {
    global $conn;
    $id = (int)$id;
    $status = (int)$status;
    $sql = "UPDATE notes SET is_archived = $status WHERE id = $id";
    return $conn->query($sql);
}

function saveNote($title, $content, $language_id = null, $tags = [], $id = null) {
    global $conn;
    $title = $conn->real_escape_string($title);
    $content = $conn->real_escape_string($content);
    $language_id = $language_id ? (int)$language_id : 'NULL';

    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE notes SET title = '$title', content = '$content', language_id = $language_id WHERE id = $id";
    } else {
        // Get min sort_order
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM notes");
        $row = $result->fetch_assoc();
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO notes (title, content, sort_order, language_id) VALUES ('$title', '$content', $next_sort, $language_id)";
    }

    if ($conn->query($sql)) {
        $note_id = $id ? $id : $conn->insert_id;
        
        // Handle tags
        $conn->query("DELETE FROM note_tags WHERE note_id = $note_id");
        foreach ($tags as $tag_id) {
            $tag_id = (int)$tag_id;
            $conn->query("INSERT INTO note_tags (note_id, tag_id) VALUES ($note_id, $tag_id)");
        }
        return true;
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

function initTodosTable() {
    global $conn;
    $conn->query("CREATE TABLE IF NOT EXISTS todos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        text VARCHAR(500) NOT NULL,
        is_archived TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conn->query("CREATE TABLE IF NOT EXISTS todo_tags (
        todo_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (todo_id, tag_id)
    )");
}

function getAllTodos($archive_status = 0) {
    global $conn;
    initTodosTable();
    $archive_status = (int)$archive_status;
    $sql = "SELECT * FROM todos WHERE is_archived = $archive_status ORDER BY sort_order ASC, created_at DESC";
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

function saveTodo($text, $tags = [], $id = null) {
    global $conn;
    initTodosTable();
    $text = $conn->real_escape_string($text);
    
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE todos SET text = '$text' WHERE id = $id";
    } else {
        $result = $conn->query("SELECT MIN(sort_order) as min_sort FROM todos");
        $row = $result ? $result->fetch_assoc() : null;
        $next_sort = $row['min_sort'] !== null ? (int)$row['min_sort'] - 1 : 0;
        $sql = "INSERT INTO todos (text, sort_order) VALUES ('$text', $next_sort)";
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
        return true;
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
?>
