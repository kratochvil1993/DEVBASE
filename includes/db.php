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

try {
    if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
        // Připojení k SQLite
        $conn = new PDO("sqlite:" . DB_SQLITE_PATH);
    } else {
        // Připojení k MySQL
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $conn = new PDO($dsn, DB_USER, DB_PASS);
    }
    
    // Nastavení PDO: vyhazování výjimek při chybách a asociativní pole jako výchozí formát
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
