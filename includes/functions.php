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
    $sql = "SELECT t.name, t.color FROM tags t 
            JOIN snippet_tags st ON t.id = st.tag_id 
            WHERE st.snippet_id = $snippet_id";
    $result = $conn->query($sql);
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    return $tags;
}

function getAllTags() {
    global $conn;
    $sql = "SELECT * FROM tags ORDER BY name ASC";
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

function saveTag($name, $color, $id = null) {
    global $conn;
    $name = $conn->real_escape_string($name);
    $color = !empty($color) ? "'" . $conn->real_escape_string($color) . "'" : "NULL";
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE tags SET name = '$name', color = $color WHERE id = $id";
    } else {
        $sql = "INSERT INTO tags (name, color) VALUES ('$name', $color)";
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

function getAllNotes() {
    global $conn;
    $sql = "SELECT * FROM notes ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    return $notes;
}

function saveNote($title, $content, $id = null) {
    global $conn;
    $title = $conn->real_escape_string($title);
    $content = $conn->real_escape_string($content);
    if ($id) {
        $id = (int)$id;
        $sql = "UPDATE notes SET title = '$title', content = '$content' WHERE id = $id";
    } else {
        $sql = "INSERT INTO notes (title, content) VALUES ('$title', '$content')";
    }
    return $conn->query($sql);
}

function deleteNote($id) {
    global $conn;
    $id = (int)$id;
    $sql = "DELETE FROM notes WHERE id = $id";
    return $conn->query($sql);
}
?>
