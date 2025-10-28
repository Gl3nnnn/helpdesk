<?php
require_once 'config.php';
require_once 'session.php';

// Initialize secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logout();
header("Location: index.php");
exit;
?>
