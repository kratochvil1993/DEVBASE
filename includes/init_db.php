<?php
$host = 'mysql_db';
$user = 'root';
$pass = 'root';
$dbname = 'devbase';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Root path to the schema
$schema_file = __DIR__ . '/../schema.sql';
if (file_exists($schema_file)) {
    $sql = file_get_contents($schema_file);
    
    // Execute multiple queries
    if ($conn->multi_query($sql)) {
        do {
            // Free results of each query
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->errno) {
        echo "<div style='color:red; font-family: sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h2>Chyba při inicializaci schéma</h2>";
        echo "<p>" . htmlspecialchars($conn->error) . "</p>";
        echo "</div>";
        $conn->close();
        exit;
    }

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
            'deadline' => 'DATE DEFAULT NULL'
        ],
        'tags' => [
            'type' => "VARCHAR(20) DEFAULT 'snippet'",
            'sort_order' => 'INT DEFAULT 0'
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

    header("Refresh: 2; URL=../index.php");
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: #2ecc71;'>Databáze a schéma byly úspěšně inicializovány.</h2>";
    echo "<p>Za okamžik budete přesměrováni na hlavní stránku...</p>";
    echo "</div>";

} else {
    echo "Schema file not found at: " . htmlspecialchars($schema_file);
}

$conn->close();
?>
