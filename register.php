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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    $username_validation = validate_form_input($username, 'username');
    $email_validation = validate_form_input($email, 'email');
    $password_validation = validate_form_input($password, 'password');

    if (!$username_validation['valid']) {
        $error = $username_validation['error'];
    } elseif (!$email_validation['valid']) {
        $error = $email_validation['error'];
    } elseif (!$password_validation['valid']) {
        $error = $password_validation['error'];
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $username = $username_validation['value'];
        $email = $email_validation['value'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed_password, 'user']);
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "Registration failed. This email might already be used.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Helpdesk</title>
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
        .register-card {
            background: #ffffffcc;
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .register-card:hover {
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }
        .register-card h2 {
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
    <div class="register-card shadow">
        <h2>Create Account</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                <small class="form-text text-muted"><?php echo get_password_requirements(); ?></small>
            </div>
            <div class="mb-3">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Register</button>
        </form>

        <p class="footer-text">
            Already have an account? <a href="login.php" class="text-link">Login here</a>
        </p>
    </div>
</body>
</html>
