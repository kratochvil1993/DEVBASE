<?php
$host = 'mysql_db';
$user = 'root';
$pass = 'root';
$dbname = 'devbase';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
