<?php
$servername = "127.0.0.1";
$username = "Police_Application";
$password = "Spearchirp@20";
$dbname = "police_application";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>