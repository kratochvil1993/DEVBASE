-- Schema definitions for DevBase (agnostic of database name)

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
    deadline_time TIME DEFAULT NULL,
    note TEXT DEFAULT NULL,
    is_archived TINYINT(1) DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    is_locked TINYINT(1) DEFAULT 0,
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES todos(id) ON DELETE CASCADE
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


CREATE TABLE IF NOT EXISTS scratchpads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) DEFAULT 'code',
    name VARCHAR(50) DEFAULT 'default',
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (name, type)
);

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
    is_seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (content_hash)
);

-- Initial DB Version is now handled by init_db.php or migrations

