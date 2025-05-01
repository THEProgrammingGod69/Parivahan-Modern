<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: Parivahan_home.html");
exit();
?>