<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: C:\Projects\Docker\php\login.php");
    exit();
}
?>