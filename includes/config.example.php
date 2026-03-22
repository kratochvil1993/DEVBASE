<?php
// =============================================================
// KONFIGURAČNÍ SOUBOR – ŠABLONA
// =============================================================
// Tento soubor je v Gitu a slouží jako šablona.
// Pro nasazení na server:
//   cp includes/config.example.php includes/config.php
// A vyplň skutečné přihlašovací údaje v config.php.
// =============================================================

// Typ databáze: 'mysql' nebo 'sqlite'
define('DB_TYPE',   'mysql');

// Nastavení pro MySQL
define('DB_HOST',   'localhost');      // host MySQL serveru (většinou 'localhost')
define('DB_USER',   'your_db_user');   // MySQL uživatelské jméno
define('DB_PASS',   'your_db_password'); // MySQL heslo
define('DB_NAME',   'devbase');        // název databáze

// Nastavení pro SQLite (v případě DB_TYPE = 'sqlite')
define('DB_SQLITE_PATH', __DIR__ . '/../database.sqlite');


//docker build
/*
define('DB_HOST',   'mysql_db');  // název Docker service z docker-compose.yml
define('DB_USER',   'root');
define('DB_PASS',   'root');
define('DB_NAME',   'devbase');
*/