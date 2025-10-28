<?php
// Security Configuration File
// This file contains all security-related settings and configurations

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'helpdesk');
define('DB_USER', 'root'); // Change this in production
define('DB_PASS', ''); // Change this in production

// Security Settings
define('SESSION_LIFETIME', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('CSRF_TOKEN_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// File Upload Settings
define('MAX_FILE_SIZE', 40 * 1024 * 1024); // 40MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Security Headers
define('ENABLE_HTTPS_REDIRECT', false);
define('ENABLE_HSTS', true);
define('ENABLE_CSP', true);
define('ENABLE_X_FRAME_OPTIONS', true);
define('ENABLE_X_CONTENT_TYPE_OPTIONS', true);
define('ENABLE_X_XSS_PROTECTION', true);

// CSP Settings
define('CSP_DEFAULT_SRC', "'self'");
define('CSP_SCRIPT_SRC', "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com");
define('CSP_STYLE_SRC', "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com");
define('CSP_IMG_SRC', "'self' data: https:");
define('CSP_FONT_SRC', "'self' https://fonts.gstatic.com");
define('CSP_CONNECT_SRC', "'self' http://localhost https://cdn.jsdelivr.net");
define('CSP_FRAME_SRC', "'none'");

// Error Reporting (set to false in production)
define('DISPLAY_ERRORS', true);
error_reporting(DISPLAY_ERRORS ? E_ALL : 0);
ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');

// Timezone
date_default_timezone_set('UTC');
?>
