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
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        // Migrations: Ensure specific columns exist even if tables already existed
        $migrations = [
            'snippets' => ['is_locked' => 'TINYINT(1) DEFAULT 0'],
            'notes' => ['is_locked' => 'TINYINT(1) DEFAULT 0'],
            'todos' => ['is_locked' => 'TINYINT(1) DEFAULT 0']
        ];

        foreach ($migrations as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $checkCol = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                if ($checkCol && $checkCol->num_rows == 0) {
                    $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                }
            }
        }

        header("Refresh: 2; URL=../index.php");
        echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h2>Databáze a schéma byly úspěšně inicializovány.</h2>";
        echo "<p>Za okamžik budete přesměrováni na hlavní stránku...</p>";
        echo "</div>";
    } else {
        echo "Error initializing schema: " . $conn->error;
    }
} else {
    echo "Schema file not found.";
}

$conn->close();
?>
