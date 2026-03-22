<?php
require_once __DIR__ . '/functions.php';

// 1. Vytvoření databáze (pouze MySQL)
if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
    $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

// 2. Import schématu (schema.sql)
$schema_file = __DIR__ . '/../schema.sql';
if (file_exists($schema_file)) {
    $sql = file_get_contents($schema_file);
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        try {
            // Robustní překlad pro SQLite
            $sqlite_sql = $sql;
            $sqlite_sql = preg_replace('/ENGINE=InnoDB/i', '', $sqlite_sql);
            $sqlite_sql = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $sqlite_sql);
            $sqlite_sql = preg_replace('/CHARACTER SET [^ ]+/i', '', $sqlite_sql);
            $sqlite_sql = preg_replace('/COLLATE [^ ]+/i', '', $sqlite_sql);
            $sqlite_sql = preg_replace('/(TINYINT|INT)\(\d+\)/i', 'INTEGER', $sqlite_sql);
            $sqlite_sql = preg_replace('/(DATETIME|TIMESTAMP)/i', 'TEXT', $sqlite_sql);
            $sqlite_sql = preg_replace('/LONGTEXT/i', 'TEXT', $sqlite_sql);
            $sqlite_sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sqlite_sql);
            $sqlite_sql = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sqlite_sql);
            
            // Rozdělení na jednotlivé příkazy (někdy PDO na SQLite neumí vše najednou přes exec)
            $statements = array_filter(array_map('trim', explode(';', $sqlite_sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    try { $conn->exec($stmt); } catch (Exception $e) {}
                }
            }
        } catch (Exception $e) {
            error_log("SQLite init warning: " . $e->getMessage());
        }
    } else {
        try {
            $conn->exec($sql);
        } catch (Exception $e) {
            echo "<div style='color:red; font-family: sans-serif; text-align: center; margin-top: 50px;'>";
            echo "<h2>Chyba při inicializaci schéma</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
            exit;
        }
    }

    // 3. Migrace - Kontrola a doplnění chybějících tabulek a sloupců
    $definitions = [
        'languages' => "id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, prism_class TEXT",
        'tags' => "id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, color TEXT, type TEXT DEFAULT 'snippet', sort_order INTEGER DEFAULT 0",
        'snippets' => "id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, description TEXT, code TEXT, language_id INTEGER, is_pinned INTEGER DEFAULT 0, is_locked INTEGER DEFAULT 0, sort_order INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP",
        'notes' => "id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, content TEXT, sort_order INTEGER DEFAULT 0, language_id INTEGER, is_pinned INTEGER DEFAULT 0, is_locked INTEGER DEFAULT 0, is_archived INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP",
        'todos' => "id INTEGER PRIMARY KEY AUTOINCREMENT, text TEXT, deadline TEXT, deadline_time TEXT, note TEXT, is_archived INTEGER DEFAULT 0, is_pinned INTEGER DEFAULT 0, is_locked INTEGER DEFAULT 0, parent_id INTEGER, sort_order INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP",
        'settings' => "setting_key TEXT PRIMARY KEY, setting_value TEXT",
        'scratchpads' => "id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT DEFAULT 'code', name TEXT, content TEXT, updated_at TEXT DEFAULT CURRENT_TIMESTAMP",
        'inbox_items' => "id INTEGER PRIMARY KEY AUTOINCREMENT, mail_uid TEXT UNIQUE, content_hash TEXT UNIQUE, subject TEXT, content TEXT, from_email TEXT, target_type TEXT DEFAULT 'unknown', target_id INTEGER, is_imported INTEGER DEFAULT 0, is_seen INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($definitions as $table => $def) {
        $tableExists = false;
        try {
            if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
                $tableExists = (bool)$conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
            } else {
                $tableExists = (bool)$conn->query("SHOW TABLES LIKE '$table'")->fetch();
            }
        } catch (Exception $e) {}

        if (!$tableExists) {
            $createSql = (defined('DB_TYPE') && DB_TYPE === 'mysql') 
                ? "CREATE TABLE `$table` (id INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" // Basic MySQL
                : "CREATE TABLE `$table` ($def)"; // Full SQLite
            
            // Special case for settings in MySQL
            if ($table === 'settings' && defined('DB_TYPE') && DB_TYPE === 'mysql') {
                $createSql = "CREATE TABLE `settings` (setting_key VARCHAR(50) PRIMARY KEY, setting_value VARCHAR(255))";
            }
            
            try { $conn->exec($createSql); } catch (Exception $e) {}
        }
    }

    // 4. Doplnění sloupců (pokud existují tabulky, ale chybí sloupce z novějších verzí nebo selhalo schéma)
    $columnMigrations = [
        'languages' => ['name' => 'TEXT', 'prism_class' => 'TEXT'],
        'snippets' => ['title' => 'TEXT', 'description' => 'TEXT', 'code' => 'TEXT', 'language_id' => 'INTEGER', 'is_locked' => 'INTEGER DEFAULT 0', 'is_pinned' => 'INTEGER DEFAULT 0', 'sort_order' => 'INTEGER DEFAULT 0'],
        'notes' => ['title' => 'TEXT', 'content' => 'TEXT', 'is_locked' => 'INTEGER DEFAULT 0', 'is_pinned' => 'INTEGER DEFAULT 0', 'is_archived' => 'INTEGER DEFAULT 0', 'sort_order' => 'INTEGER DEFAULT 0', 'language_id' => 'INTEGER DEFAULT NULL'],
        'todos' => ['text' => 'TEXT', 'is_locked' => 'INTEGER DEFAULT 0', 'is_pinned' => 'INTEGER DEFAULT 0', 'is_archived' => 'INTEGER DEFAULT 0', 'sort_order' => 'INTEGER DEFAULT 0', 'deadline' => 'TEXT DEFAULT NULL', 'deadline_time' => 'TEXT DEFAULT NULL', 'note' => 'TEXT DEFAULT NULL', 'parent_id' => 'INTEGER DEFAULT NULL'],
        'tags' => ['name' => 'TEXT', 'type' => "TEXT DEFAULT 'snippet'", 'sort_order' => 'INTEGER DEFAULT 0', 'color' => 'TEXT'],
        'scratchpads' => ['type' => "TEXT DEFAULT 'code'", 'name' => "TEXT DEFAULT 'default'", 'content' => 'TEXT', 'updated_at' => 'TEXT']
    ];

    foreach ($columnMigrations as $table => $columns) {
        $existingColumns = [];
        try {
            if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
                $stmt = $conn->query("PRAGMA table_info(`$table`)");
                if ($stmt) while ($row = $stmt->fetch()) $existingColumns[] = strtolower($row['name']);
            } else {
                $stmt = $conn->query("SHOW COLUMNS FROM `$table`");
                if ($stmt) while ($row = $stmt->fetch()) $existingColumns[] = strtolower($row['Field']);
            }
        } catch (Exception $e) {}

        foreach ($columns as $column => $definition) {
            if (!in_array(strtolower($column), $existingColumns)) {
                try { $conn->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition"); } catch (Exception $e) {}
            }
        }
    }

    // 4. Indexy (MySQL pouze)
    if (defined('DB_TYPE') && DB_TYPE === 'mysql') {
        try {
            @$conn->exec("ALTER TABLE `tags` ADD UNIQUE INDEX `unique_name_type` (`name`, `type`)");
        } catch(Exception $e) {}
    }

    // 5. Seed data
    $tablesToSeed = ['settings', 'languages', 'tags', 'scratchpads', 'snippets', 'notes'];
    foreach ($tablesToSeed as $table) {
        try {
            $rowCount = (int)$conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            if ($rowCount === 0) {
                if ($table === 'settings') {
                    $conn->exec("INSERT INTO settings (setting_key, setting_value) VALUES 
                        ('snippets_enabled', '1'), ('notes_enabled', '1'), ('todos_enabled', '1'), 
                        ('code_enabled', '1'), ('todo_badge_enabled', '1'), ('theme_toggle_enabled', '0'), 
                        ('security_enabled', '0'), ('note_drafts_enabled', '1'), ('gemini_model', 'gemini-1.5-flash'), 
                        ('ai_provider', 'gemini'), ('inbox_enabled', '0'), ('db_version', '1.2.2')");
                } elseif ($table === 'languages') {
                    $conn->exec("INSERT INTO languages (name, prism_class) VALUES 
                        ('PHP', 'php'), ('JavaScript', 'javascript'), ('HTML', 'html'), 
                        ('CSS', 'css'), ('SQL', 'sql'), ('Python', 'python'), ('Bash', 'bash')");
                } elseif ($table === 'tags') {
                    $conn->exec("INSERT INTO tags (name, type, color) VALUES 
                        ('Frontend', 'snippet', '#3498db'), ('Backend', 'snippet', '#2ecc71'), 
                        ('Database', 'snippet', '#f1c40f'), ('Důležité', 'note', '#e74c3c'), 
                        ('Práce', 'todo', '#9b59b6'), ('Osobní', 'todo', '#1abc9c')");
                } elseif ($table === 'scratchpads') {
                    $conn->exec("INSERT INTO scratchpads (name, content, type) VALUES ('default', '// Vítejte v editoru kódu.', 'code'), ('Poznámky', '<h1>Poznámky</h1>', 'note')");
                }
            }
        } catch (Exception $e) {}
    }

    // Vždy zajistit aktuální verzi v settings
    updateSetting('db_version', '1.2.2');

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2ecc71;'>Inicializace dokončena.</h2>";
    echo "<p>Budete přesměrováni...</p>";
    echo "</div>";
    echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 1500);</script>";

} else {
    echo "Schema file not found at: " . htmlspecialchars($schema_file);
}

$conn = null;
?>
