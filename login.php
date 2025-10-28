<?php
require_once 'config.php';
require_once 'security_headers.php';
require_once 'session.php';
require_once 'validation.php';
require_once 'db.php';

// Initialize security features
enforce_https();
set_security_headers();

init_secure_session();

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: " . (has_role('admin') ? 'admin_dashboard.php' : 'view_tickets.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate email format
    $email_validation = validate_form_input($email, 'email');
    if (!$email_validation['valid']) {
        $error = $email_validation['error'];
    } else {
        $email = $email_validation['value'];

        // Check login attempts
        $attempt_check = check_login_attempts($email);
        if (!$attempt_check['allowed']) {
            $error = "Too many failed login attempts. Please wait " . ceil($attempt_check['wait_time'] / 60) . " minutes.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] != 'active') {
                        $error = "Your account is inactive. Please contact the admin.";
                    } else {
                        // Successful login - reset attempts and set session
                        reset_login_attempts($email);

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['login_time'] = time();

                        // Log successful login (you might want to implement audit logging)
                        error_log("User {$user['username']} logged in from {$_SERVER['REMOTE_ADDR']}");

                        header("Location: " . ($user['role'] == 'admin' || $user['role'] == 'top_admin' ? 'admin_dashboard.php' : 'view_tickets.php'));
                        exit;
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } catch (PDOException $e) {
                error_log("Login database error: " . $e->getMessage());
                $error = "Login failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            background: linear-gradient(145deg, #eef2f3, #dfe6e9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Poppins", sans-serif;
            color: #333;
        }
        .login-card {
            background: #ffffffcc;
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .login-card:hover {
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }
        .login-card h2 {
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #444;
        }
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 10px 12px;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #a1c4fd;
            box-shadow: 0 0 0 3px rgba(161, 196, 253, 0.25);
        }
        .btn-primary {
            background: linear-gradient(120deg, #a1c4fd, #c2e9fb);
            border: none;
            color: #333;
            font-weight: 500;
            border-radius: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(120deg, #c2e9fb, #a1c4fd);
            transform: translateY(-1px);
        }
        .alert {
            text-align: center;
            border-radius: 10px;
        }
        .footer-text {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }
        .text-link {
            color: #5c7cfa;
            text-decoration: none;
        }
        .text-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card shadow">
        <h2>Helpdesk Login</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Login</button>
        </form>

        <p class="footer-text">
            Don’t have an account? <a href="register.php" class="text-link">Register here</a>
        </p>
    </div>
</body>
</html>
