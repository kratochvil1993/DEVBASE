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
    note TEXT DEFAULT NULL,
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
('security_enabled', '0'),
('note_drafts_enabled', '1'),
('gemini_model', 'gemini-2.5-flash-lite'),
('ai_provider', 'gemini'),
('openai_api_key', ''),
('openai_model', 'gpt-4o-mini');

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

CREATE TABLE IF NOT EXISTS scratchpads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) DEFAULT 'code',
    name VARCHAR(50) DEFAULT 'default',
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (name, type)
);

INSERT IGNORE INTO scratchpads (name, content, type) VALUES ('default', '// Vítejte v editoru kódu. Zde si můžete psát poznámky nebo kód.', 'code');
INSERT IGNORE INTO scratchpads (name, content, type) VALUES ('Poznámky', '<h1>Vítejte v poznámkovém draftu</h1><p>Zde si můžete psát rychlé poznámky...</p>', 'note');

CREATE TABLE IF NOT EXISTS inbox_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mail_uid VARCHAR(100) UNIQUE,
    content_hash VARCHAR(32),
    subject VARCHAR(255),
    content TEXT,
    from_email VARCHAR(255),
    target_type ENUM('note', 'todo', 'draft', 'unknown') DEFAULT 'unknown',
    target_id INT DEFAULT NULL,
    is_imported TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (content_hash)
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('inbox_enabled', '0'),
('imap_server', ''),
('imap_port', '993'),
('imap_user', ''),
('imap_password', ''),
('imap_encryption', 'ssl'),
('smtp_server', ''),
('smtp_port', '465'),
('smtp_user', ''),
('smtp_password', ''),
('smtp_encryption', 'ssl'),
('inbox_trusted_emails', ''),
('inbox_auto_check', '0');

