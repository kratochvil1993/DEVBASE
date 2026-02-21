CREATE DATABASE IF NOT EXISTS devbase;
USE devbase;

CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    prism_class VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS snippets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    code TEXT NOT NULL,
    language_id INT,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255)
);

-- Seed initial data
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('notes_enabled', '1');

-- Seed initial data
INSERT IGNORE INTO languages (name, prism_class) VALUES 
('PHP', 'php'),
('JavaScript', 'javascript'),
('HTML', 'html'),
('CSS', 'css'),
('SQL', 'sql'),
('Python', 'python');

INSERT IGNORE INTO tags (name) VALUES 
('Frontend'),
('Backend'),
('Database'),
('Security'),
('UI/UX'),
('Utility'),
('React'),
('API');

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
(1, (SELECT id FROM tags WHERE name = 'Backend')),
(1, (SELECT id FROM tags WHERE name = 'Database')),
(2, (SELECT id FROM tags WHERE name = 'Frontend')),
(2, (SELECT id FROM tags WHERE name = 'API')),
(3, (SELECT id FROM tags WHERE name = 'UI/UX')),
(3, (SELECT id FROM tags WHERE name = 'Frontend'));
