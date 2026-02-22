<?php
$host = 'mysql_db';
$user = 'root';
$pass = 'root';
$dbname = 'devbase';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Try to select database, but don't die if it fails (allows init_db process)
$conn->select_db($dbname);

$conn->set_charset("utf8mb4");
?>
