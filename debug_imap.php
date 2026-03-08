<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

try {
    $cm = new Webklex\PHPIMAP\ClientManager();
    echo "Class loaded successfully!";
} catch (Error $e) {
    echo "Error loading class: " . $e->getMessage();
} catch (Exception $e) {
    echo "Exception loading class: " . $e->getMessage();
}
