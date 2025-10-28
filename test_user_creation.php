<?php
require_once 'db.php';
require_once 'csrf.php';
require_once 'validation.php';
require_once 'session.php';
require_once 'security_headers.php';

// Simulate session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Simulate POST data
$_POST = [
    'create_user' => '1',
    'new_username' => 'testuser',
    'new_email' => 'test@example.com',
    'new_password' => 'TestPass123!',
    'new_confirm_password' => 'TestPass123!',
    'new_role' => 'user',
    'csrf_token' => csrf_token(),
    'ajax' => '1'
];

// Include the user_management.php logic
ob_start();
include 'user_management.php';
$content = ob_get_clean();

echo 'Output: ' . $content;
?>
