<?php
// Načti konfiguraci z config.php (není v Gitu – na každém serveru jiná)
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Try to select database, but don't die if it fails (allows init_db process)
$conn->select_db(DB_NAME);

$conn->set_charset("utf8mb4");
?>
