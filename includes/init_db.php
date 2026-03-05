<?php
require_once __DIR__ . '/db.php';

// If connection failed in db.php, it would have died there.
// But we need a connection WITHOUT a database selected to create it if it doesn't exist.
// Re-using the same variables from db.php (they are in the global scope since they were defined at the top of db.php)

// Check if database exists by trying to select it
if (!$conn->select_db($dbname)) {
    // Database doesn't exist, connect without database to create it
    $conn_init = new mysqli($host, $user, $pass);
    if ($conn_init->connect_error) {
        die("Connection failed: " . $conn_init->connect_error);
    }
    $conn_init->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn_init->close();
    
    // Now select it on the main connection
    $conn->select_db($dbname);
}

// Ensure charset is set for schema import
$conn->set_charset("utf8mb4");

// Root path to the schema
$schema_file = __DIR__ . '/../schema.sql';
if (file_exists($schema_file)) {
    $sql = file_get_contents($schema_file);
    
    // Execute multiple queries
    if (!$conn->multi_query($sql)) {
        echo "<div style='color:red; font-family: sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h2>Chyba při startu importu schéma</h2>";
        echo "<p>" . htmlspecialchars($conn->error) . "</p>";
        echo "</div>";
        $conn->close();
        exit;
    }

    do {
        // Free results of each query
        if ($result = $conn->store_result()) {
            $result->free();
        }

        if ($conn->errno) {
            echo "<div style='color:red; font-family: sans-serif; text-align: center; margin-top: 50px;'>";
            echo "<h2>Chyba při inicializaci schéma</h2>";
            echo "<p>" . htmlspecialchars($conn->error) . "</p>";
            echo "</div>";
            $conn->close();
            exit;
        }
    } while ($conn->more_results() && $conn->next_result());

    // Migrations: Ensure specific columns exist even if tables already existed
    $migrations = [
        'snippets' => [
            'is_locked' => 'TINYINT(1) DEFAULT 0',
            'is_pinned' => 'TINYINT(1) DEFAULT 0',
            'sort_order' => 'INT DEFAULT 0'
        ],
        'notes' => [
            'is_locked' => 'TINYINT(1) DEFAULT 0',
            'is_pinned' => 'TINYINT(1) DEFAULT 0',
            'is_archived' => 'TINYINT(1) DEFAULT 0',
            'sort_order' => 'INT DEFAULT 0',
            'language_id' => 'INT DEFAULT NULL'
        ],
        'todos' => [
            'is_locked' => 'TINYINT(1) DEFAULT 0',
            'is_pinned' => 'TINYINT(1) DEFAULT 0',
            'is_archived' => 'TINYINT(1) DEFAULT 0',
            'sort_order' => 'INT DEFAULT 0',
            'deadline' => 'DATE DEFAULT NULL',
            'deadline_time' => 'TIME DEFAULT NULL',
            'note' => 'TEXT DEFAULT NULL'
        ],
        'tags' => [
            'type' => "VARCHAR(20) DEFAULT 'snippet'",
            'sort_order' => 'INT DEFAULT 0'
        ],
        'inbox_items' => [
            'mail_uid' => 'VARCHAR(100) UNIQUE',
            'content_hash' => 'VARCHAR(32) UNIQUE',
            'subject' => 'VARCHAR(255)',
            'content' => 'TEXT',
            'from_email' => 'VARCHAR(255)',
            'target_type' => "ENUM('note', 'todo', 'draft', 'unknown') DEFAULT 'unknown'",
            'target_id' => 'INT DEFAULT NULL',
            'is_imported' => 'TINYINT(1) DEFAULT 0',
            'is_seen' => 'TINYINT(1) DEFAULT 0'
        ],
        'scratchpads' => [
            'type' => "VARCHAR(20) DEFAULT 'code'",
            'name' => "VARCHAR(50) DEFAULT 'default'",
            'content' => 'LONGTEXT'
        ]
    ];

    foreach ($migrations as $table => $columns) {
        foreach ($columns as $column => $definition) {
            $checkCol = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($checkCol && $checkCol->num_rows == 0) {
                $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            }
        }
    }

    // Migration: Fix UNIQUE index on tags table
    // Old schema had UNIQUE(name) only, which prevents same tag name for different types.
    // New schema needs UNIQUE(name, type) to allow e.g. "inbox" for both notes and todos.
    $oldUniqueExists = false;
    $newUniqueExists = false;
    $indexRes = $conn->query("SHOW INDEX FROM `tags`");
    if ($indexRes) {
        $indexedColumns = [];
        $keyGroups = [];
        while ($idxRow = $indexRes->fetch_assoc()) {
            if ($idxRow['Non_unique'] == 0) { // unique index
                $keyGroups[$idxRow['Key_name']][] = $idxRow['Column_name'];
            }
        }
        foreach ($keyGroups as $keyName => $cols) {
            sort($cols);
            if ($cols === ['name'] && $keyName !== 'PRIMARY') {
                $oldUniqueExists = $keyName; // store the key name so we can drop it
            }
            if ($cols === ['name', 'type']) {
                $newUniqueExists = true;
            }
        }
    }
    // Drop old name-only unique index if found
    if ($oldUniqueExists) {
        $conn->query("ALTER TABLE `tags` DROP INDEX `$oldUniqueExists`");
    }
    // Add correct (name, type) unique index if missing
    if (!$newUniqueExists) {
        $conn->query("ALTER TABLE `tags` ADD UNIQUE INDEX `unique_name_type` (`name`, `type`)");
    }


    // Seed settings ONLY if settings table is empty or missing those keys
    $checkSettings = $conn->query("SELECT setting_key FROM settings LIMIT 1");
    if ($checkSettings && $checkSettings->num_rows == 0) {
        $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
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
        ('inbox_enabled', '0')");
    }

    // Seed Languages ONLY if empty
    $checkLangs = $conn->query("SELECT id FROM languages LIMIT 1");
    if ($checkLangs && $checkLangs->num_rows == 0) {
        $conn->query("INSERT IGNORE INTO languages (name, prism_class) VALUES 
        ('PHP', 'php'),
        ('JavaScript', 'javascript'),
        ('HTML', 'html'),
        ('CSS', 'css'),
        ('SQL', 'sql'),
        ('Python', 'python'),
        ('Bash', 'bash')");
    }

    // Seed Tags ONLY if empty
    $checkTagsEmpty = $conn->query("SELECT id FROM tags LIMIT 1");
    if ($checkTagsEmpty && $checkTagsEmpty->num_rows == 0) {
        $conn->query("INSERT IGNORE INTO tags (name, type, color) VALUES 
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
        ('Archiv', 'note', '#95a5a6')");
    }

    // Seed Scratchpads ONLY if empty
    $checkPads = $conn->query("SELECT id FROM scratchpads LIMIT 1");
    if ($checkPads && $checkPads->num_rows == 0) {
        $conn->query("INSERT IGNORE INTO scratchpads (name, content, type) VALUES ('default', '// Vítejte v editoru kódu. Zde si můžete psát poznámky nebo kód.', 'code')");
        $conn->query("INSERT IGNORE INTO scratchpads (name, content, type) VALUES ('Poznámky', '<h1>Vítejte v poznámkovém draftu</h1><p>Zde si můžete psát rychlé poznámky...</p>', 'note')");
    }

    // Seed sample data ONLY if tables are empty
    $checkSnippets = $conn->query("SELECT id FROM snippets LIMIT 1");
    if ($checkSnippets && $checkSnippets->num_rows == 0) {
        // Seed Snippets
        $conn->query("INSERT INTO snippets (title, description, code, language_id) VALUES 
        ('PHP PDO Connection', 'A standard way to connect to MySQL using PDO with error handling.', '<?php\ntry {\n    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$db\", \$user, \$pass);\n    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n    echo \"Connected successfully\";\n} catch(PDOException \$e) {\n    echo \"Connection failed: \" . \$e->getMessage();\n}\n?>', (SELECT id FROM languages WHERE name = 'PHP' LIMIT 1)),
        ('JS Fetch API', 'Example of using the Fetch API to get data from a JSON endpoint.', 'fetch(\'https://api.example.com/data\')\n  .then(response => response.json())\n  .then(data => console.log(data))\n  .catch(error => console.error(\'Error:\', error));', (SELECT id FROM languages WHERE name = 'JavaScript' LIMIT 1))");
        
        $lastId = $conn->insert_id;
        $tIdRes = $conn->query("SELECT id FROM tags WHERE name = 'Backend' AND type = 'snippet' LIMIT 1");
        $tId = ($tIdRes && $tIdRes->num_rows > 0) ? $tIdRes->fetch_assoc()['id'] : null;
        if ($lastId && $tId) {
            $conn->query("INSERT IGNORE INTO snippet_tags (snippet_id, tag_id) VALUES ($lastId, $tId)");
        }
    }

    $checkNotes = $conn->query("SELECT id FROM notes LIMIT 1");
    if ($checkNotes && $checkNotes->num_rows == 0) {
        $conn->query("INSERT INTO notes (title, content) VALUES 
        ('Vítejte v DevBase', 'Toto je vaše první poznámka. DevBase vám umožňuje ukládat kousky kódu, poznámky a úkoly na jednom místě.'),
        ('Můj první draft', 'Zde si můžete psát své nápady, které později rozpracujete.')");
    }
    
    // Update DB Version
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('db_version', '1.2.1') ON DUPLICATE KEY UPDATE setting_value = '1.2.1'");

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2ecc71;'>Databáze a schéma byly úspěšně inicializovány.</h2>";
    echo "<p>Položky byly vytvořeny. Za okamžik budete přesměrováni...</p>";
    echo "</div>";
    
    // Automatic redirect back to root
    echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 2000);</script>";

} else {
    echo "Schema file not found at: " . htmlspecialchars($schema_file);
}

$conn->close();
?>
