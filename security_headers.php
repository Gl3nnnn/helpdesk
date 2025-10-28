<?php
/**
 * Security Headers Implementation
 * Sets various security headers to protect against common web vulnerabilities
 */

function set_security_headers() {
    // Prevent clickjacking
    if (ENABLE_X_FRAME_OPTIONS) {
        header('X-Frame-Options: DENY');
    }

    // Prevent MIME type sniffing
    if (ENABLE_X_CONTENT_TYPE_OPTIONS) {
        header('X-Content-Type-Options: nosniff');
    }

    // Enable XSS protection
    if (ENABLE_X_XSS_PROTECTION) {
        header('X-XSS-Protection: 1; mode=block');
    }

    // HTTP Strict Transport Security (HSTS)
    if (ENABLE_HSTS && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Content Security Policy (CSP)
    if (ENABLE_CSP) {
        $csp = "default-src " . CSP_DEFAULT_SRC . "; " .
               "script-src " . CSP_SCRIPT_SRC . "; " .
               "style-src " . CSP_STYLE_SRC . "; " .
               "img-src " . CSP_IMG_SRC . "; " .
               "font-src " . CSP_FONT_SRC . "; " .
               "connect-src " . CSP_CONNECT_SRC . "; " .
               "frame-src " . CSP_FRAME_SRC . ";";
        header("Content-Security-Policy: " . $csp);
    }

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Feature Policy / Permissions Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

    // Remove server information
    header_remove('X-Powered-By');
    header_remove('Server');
}

/**
 * Force HTTPS redirect
 */
function enforce_https() {
    if (ENABLE_HTTPS_REDIRECT && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $url, true, 301);
        exit;
    }
}
?>
