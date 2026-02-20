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
        echo "Database and schema initialized successfully.";
    } else {
        echo "Error initializing schema: " . $conn->error;
    }
} else {
    echo "Schema file not found.";
}

$conn->close();
?>
