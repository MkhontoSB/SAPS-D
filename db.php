<?php

$config = require __DIR__ . '/config/database.php';

$servername = $config['host'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['database'];

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname
);

if ($conn->connect_error) {
    die("Database connection failed.");
}
?>