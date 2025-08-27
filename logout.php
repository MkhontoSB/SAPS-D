<?php
// logout.php
session_start();


$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
