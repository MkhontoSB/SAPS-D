<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("C:\xampp\htdocs\DockSAP\login.php");
    exit();
}
?>