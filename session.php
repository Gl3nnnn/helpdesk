<?php
/**
 * Secure Session Management
 * Provides enhanced session security features
 */

require_once 'config.php';

/**
 * Initialize secure session
 */
function init_secure_session() {
    // Only set cookie parameters if session hasn't started yet
    if (session_status() === PHP_SESSION_NONE) {
        // Set session cookie parameters for security
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();
    }

    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 300) { // Regenerate every 5 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }

    // Check session expiry
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();

    // Store user agent and IP for additional security
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    } else {
        // Check if user agent or IP changed (possible session hijacking)
        if (($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) ||
            ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? ''))) {
            session_destroy();
            header('Location: login.php?security=1');
            exit;
        }
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login for protected pages
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check user role
 */
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require specific role
 */
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        http_response_code(403);
        die('Access denied. Insufficient privileges.');
    }
}

/**
 * Logout user securely
 */
function logout() {
    // Clear all session data
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}

/**
 * Rate limiting for login attempts
 */
function check_login_attempts($email) {
    $attempts_key = 'login_attempts_' . md5($email);

    if (!isset($_SESSION[$attempts_key])) {
        $_SESSION[$attempts_key] = ['count' => 0, 'last_attempt' => 0];
    }

    $attempts = &$_SESSION[$attempts_key];

    // Reset counter if enough time has passed
    if (time() - $attempts['last_attempt'] > LOGIN_LOCKOUT_TIME) {
        $attempts['count'] = 0;
    }

    $attempts['last_attempt'] = time();

    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        return ['allowed' => false, 'wait_time' => LOGIN_LOCKOUT_TIME - (time() - $attempts['last_attempt'])];
    }

    $attempts['count']++;
    return ['allowed' => true];
}

/**
 * Reset login attempts on successful login
 */
function reset_login_attempts($email) {
    $attempts_key = 'login_attempts_' . md5($email);
    unset($_SESSION[$attempts_key]);
}
?>
