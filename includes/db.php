<?php
// Načti konfiguraci z config.php (není v Gitu – na každém serveru jiná)
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die("<h3>Chyba: Konfigurační soubor nenalezen!</h3>
        <p>Soubor <code>includes/config.php</code> neexistuje.</p>
        <p>Pro zprovoznění aplikace zkopírujte <code>includes/config.example.php</code> do <code>includes/config.php</code> a nastavte přihlašovací údaje k databázi.</p>
        <pre>cp includes/config.example.php includes/config.php</pre>");
}
require_once $configPath;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Try to select database, but don't die if it fails (allows init_db process)
$conn->select_db(DB_NAME);

$conn->set_charset("utf8mb4");
?>
