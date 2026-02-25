CREATE DATABASE IF NOT EXISTS devbase;
USE devbase;

CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    prism_class VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT NULL,
    type VARCHAR(20) DEFAULT 'snippet',
    sort_order INT DEFAULT 0,
    UNIQUE (name, type)
);

CREATE TABLE IF NOT EXISTS snippets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    code TEXT NOT NULL,
    language_id INT,
    is_pinned TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS snippet_tags (
    snippet_id INT,
    tag_id INT,
    PRIMARY KEY (snippet_id, tag_id),
    FOREIGN KEY (snippet_id) REFERENCES snippets(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    language_id INT,
    is_pinned TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS note_tags (
    note_id INT,
    tag_id INT,
    PRIMARY KEY (note_id, tag_id),
    FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(500) NOT NULL,
    deadline DATE DEFAULT NULL,
    is_archived TINYINT(1) DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS todo_tags (
    todo_id INT,
    tag_id INT,
    PRIMARY KEY (todo_id, tag_id),
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
);

-- Seed initial data
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('snippets_enabled', '1'),
('notes_enabled', '1'),
('todos_enabled', '1'),
('code_enabled', '1'),
('todo_badge_enabled', '1'),
('theme_toggle_enabled', '0'),
('security_enabled', '0');

-- Seed initial data
INSERT IGNORE INTO languages (name, prism_class) VALUES 
('PHP', 'php'),
('JavaScript', 'javascript'),
('HTML', 'html'),
('CSS', 'css'),
('SQL', 'sql'),
('Python', 'python'),
('Bash', 'bash');

-- Seed initial tags with types and colors
INSERT IGNORE INTO tags (name, type, color) VALUES 
('Frontend', 'snippet', '#3498db'),
('Backend', 'snippet', '#2ecc71'),
('Database', 'snippet', '#f1c40f'),
('Důležité', 'note', '#e74c3c'),
('Důležité', 'snippet', '#e74c3c'),
('Práce', 'todo', '#9b59b6'),
('Osobní', 'todo', '#1abc9c'),
('Studium', 'todo', '#34495e'),
('Chill', 'todo', '#2980b9'),
('Nápady', 'note', '#f39c12'),
('Archiv', 'note', '#95a5a6');

-- Sample Snippets
INSERT INTO snippets (title, description, code, language_id) VALUES 
('PHP PDO Connection', 'A standard way to connect to MySQL using PDO with error handling.', '<?php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>', (SELECT id FROM languages WHERE name = 'PHP')),
('JS Fetch API', 'Example of using the Fetch API to get data from a JSON endpoint.', 'fetch(\'https://api.example.com/data\')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error(\'Error:\', error));', (SELECT id FROM languages WHERE name = 'JavaScript')),
('CSS Glassmorphism Card', 'CSS classes to create a sleek glassmorphism effect for cards.', '.glass-card {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}', (SELECT id FROM languages WHERE name = 'CSS'));

-- Tag Sample Snippets
INSERT INTO snippet_tags (snippet_id, tag_id) VALUES 
((SELECT id FROM snippets WHERE title = 'PHP PDO Connection' LIMIT 1), (SELECT id FROM tags WHERE name = 'Backend' AND type = 'snippet' LIMIT 1)),
((SELECT id FROM snippets WHERE title = 'PHP PDO Connection' LIMIT 1), (SELECT id FROM tags WHERE name = 'Database' AND type = 'snippet' LIMIT 1)),
((SELECT id FROM snippets WHERE title = 'JS Fetch API' LIMIT 1), (SELECT id FROM tags WHERE name = 'Frontend' AND type = 'snippet' LIMIT 1)),
((SELECT id FROM snippets WHERE title = 'CSS Glassmorphism Card' LIMIT 1), (SELECT id FROM tags WHERE name = 'Frontend' AND type = 'snippet' LIMIT 1));

-- Sample Notes
INSERT INTO notes (title, content) VALUES 
('Vítejte v DevBase', 'Toto je vaše první poznámka. DevBase vám umožňuje ukládat kousky kódu, poznámky a úkoly na jednom místě.'),
('Tipy pro Markdown', 'V poznámkách můžete používat standardní text nebo si je organizovat pomocí štítků.'),
('Můj první draft', 'Zde si můžete psát své nápady, které později rozpracujete.');

-- Tag Sample Notes
INSERT INTO note_tags (note_id, tag_id) VALUES 
((SELECT id FROM notes WHERE title = 'Vítejte v DevBase' LIMIT 1), (SELECT id FROM tags WHERE name = 'Důležité' AND type = 'note' LIMIT 1)),
((SELECT id FROM notes WHERE title = 'Tipy pro Markdown' LIMIT 1), (SELECT id FROM tags WHERE name = 'Důležité' AND type = 'note' LIMIT 1)),
((SELECT id FROM notes WHERE title = 'Můj první draft' LIMIT 1), (SELECT id FROM tags WHERE name = 'Nápady' AND type = 'note' LIMIT 1));

-- Sample Todos
INSERT INTO todos (text) VALUES 
('Prozkoumat funkce DevBase'),
('Uložit si první vlastní snippet'),
('Naučit se pokročilé SQL dotazy');

-- Tag Sample Todos
INSERT INTO todo_tags (todo_id, tag_id) VALUES 
((SELECT id FROM todos WHERE text = 'Prozkoumat funkce DevBase' LIMIT 1), (SELECT id FROM tags WHERE name = 'Práce' AND type = 'todo' LIMIT 1)),
((SELECT id FROM todos WHERE text = 'Uložit si první vlastní snippet' LIMIT 1), (SELECT id FROM tags WHERE name = 'Osobní' AND type = 'todo' LIMIT 1)),
((SELECT id FROM todos WHERE text = 'Naučit se pokročilé SQL dotazy' LIMIT 1), (SELECT id FROM tags WHERE name = 'Studium' AND type = 'todo' LIMIT 1));

CREATE TABLE IF NOT EXISTS scratchpads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) DEFAULT 'default',
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO scratchpads (name, content) VALUES ('default', '// Vítejte v editoru kódu. Zde si můžete psát poznámky nebo kód.');

