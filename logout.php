<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    logActivity('Logout', 'User logged out');
}

session_unset();
session_destroy();
header("Location: login.php");
exit();
?>