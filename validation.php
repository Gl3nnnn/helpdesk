<?php
/**
 * Input Validation and Sanitization Functions
 * Provides comprehensive validation and sanitization for user inputs
 */

/**
 * Sanitize string input
 */
function sanitize_string($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validate_password($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }

    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        return false;
    }

    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        return false;
    }

    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        return false;
    }

    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        return false;
    }

    return true;
}

/**
 * Get password requirements message
 */
function get_password_requirements() {
    $requirements = [];

    $requirements[] = "At least " . PASSWORD_MIN_LENGTH . " characters";

    if (PASSWORD_REQUIRE_UPPERCASE) {
        $requirements[] = "One uppercase letter";
    }

    if (PASSWORD_REQUIRE_LOWERCASE) {
        $requirements[] = "One lowercase letter";
    }

    if (PASSWORD_REQUIRE_NUMBERS) {
        $requirements[] = "One number";
    }

    if (PASSWORD_REQUIRE_SPECIAL) {
        $requirements[] = "One special character";
    }

    return implode(", ", $requirements);
}

/**
 * Validate username
 */
function validate_username($username) {
    // Username should be 3-50 characters, alphanumeric and underscores only
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * Validate file upload
 */
function validate_file_upload($file) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File size exceeds ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB limit'];
    }

    // Check file extension
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
        return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_FILE_TYPES)];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain'
    ];

    if (!isset($allowedMimeTypes[$fileExt]) || $mimeType !== $allowedMimeTypes[$fileExt]) {
        return ['valid' => false, 'error' => 'Invalid file content'];
    }

    return ['valid' => true];
}

/**
 * Generate secure filename
 */
function generate_secure_filename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

/**
 * Validate and sanitize form input
 */
function validate_form_input($input, $type = 'string') {
    switch ($type) {
        case 'email':
            if (!validate_email($input)) {
                return ['valid' => false, 'error' => 'Invalid email address'];
            }
            return ['valid' => true, 'value' => sanitize_string($input)];

        case 'username':
            if (!validate_username($input)) {
                return ['valid' => false, 'error' => 'Username must be 3-50 characters, alphanumeric and underscores only'];
            }
            return ['valid' => true, 'value' => sanitize_string($input)];

        case 'password':
            if (!validate_password($input)) {
                return ['valid' => false, 'error' => 'Password does not meet requirements: ' . get_password_requirements()];
            }
            return ['valid' => true, 'value' => $input]; // Don't sanitize passwords

        default:
            return ['valid' => true, 'value' => sanitize_string($input)];
    }
}
?>
