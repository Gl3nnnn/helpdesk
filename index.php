<?php
require_once 'config.php';
require_once 'security_headers.php';
require_once 'csrf.php';
require_once 'session.php';

// Initialize security features
enforce_https();
set_security_headers();
init_secure_session();

if (is_logged_in()) {
    if (has_role('admin') || has_role('top_admin')) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: view_tickets.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Helpdesk Ticketing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #eef2f3, #dfe6e9);
            font-family: "Poppins", sans-serif;
            color: #333;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* 🌸 Navbar Style */
        .soft-navbar {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-size: 1.3rem;
            background: linear-gradient(120deg, #a1c4fd, #c2e9fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.3px;
        }

        .nav-link {
            color: #555 !important;
            font-weight: 500;
            margin: 0 6px;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .nav-link:hover {
            background: rgba(161, 196, 253, 0.25);
            color: #0d6efd !important;
        }

        .login-btn {
            background: transparent;
            border: 1px solid #a1c4fd;
            color: #4a4a4a;
            font-weight: 500;
            padding: 6px 14px;
            transition: all 0.2s ease-in-out;
        }

        .login-btn:hover {
            background: linear-gradient(120deg, #a1c4fd, #c2e9fb);
            border-color: transparent;
            color: #333;
        }

        .register-btn {
            background: linear-gradient(120deg, #a1c4fd, #c2e9fb);
            border: none;
            color: #333;
            font-weight: 500;
            padding: 6px 14px;
            transition: all 0.2s ease-in-out;
        }

        .register-btn:hover {
            background: linear-gradient(120deg, #c2e9fb, #a1c4fd);
        }

        .logout-btn {
            background: transparent;
            border: 1px solid #ffb3b3;
            color: #d9534f;
            font-weight: 500;
            padding: 6px 14px;
            transition: all 0.2s ease-in-out;
        }

        .logout-btn:hover {
            background: #ffe5e5;
            border-color: transparent;
            color: #b52b27;
        }

        /* 🌿 Card Section */
        .card {
            background: #ffffffcc;
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            animation: fadeInUp 1s ease;
            margin-top: 80px;
        }

        .card:hover {
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
        }

        .login-logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
        }

        h5 {
            color: #4a5568;
            font-weight: 600;
        }

        h2 {
            font-weight: 600;
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

        .btn-outline-primary {
            border-color: #a1c4fd;
            color: #4a5568;
            border-radius: 10px;
            font-weight: 500;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(120deg, #a1c4fd, #c2e9fb);
            color: #333;
        }

        footer {
            color: #888;
        }

        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>
<body data-user-role="<?php echo $user_role; ?>">

    <!-- 🌸 Navbar -->
    <nav class="navbar navbar-expand-lg soft-navbar fixed-top">
      <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><img src="image/3.png" alt="Logo" style="width: 30px; height: 30px; margin-right: 8px;">Helpdesk</a>
        <button class="navbar-toggler shadow-none border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
              <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="user_management.php">Users</a></li>
              <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="view_tickets.php">My Tickets</a></li>
              <?php endif; ?>
              <li class="nav-item">
                <a class="btn logout-btn ms-2 rounded-3" href="logout.php">Logout</a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="btn login-btn rounded-3" href="login.php">Login</a>
              </li>
              <li class="nav-item ms-2">
                <a class="btn register-btn rounded-3" href="register.php">Register</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>

    <!-- 🌿 Login Form -->
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow-lg rounded-4">
            <div class="text-center mb-4">
                <img src="image/3.png" alt="Helpdesk Logo" class="login-logo mb-2">
                <h5 class="fw-bold">Helpdesk Ticketing System</h5>
            </div>

            <h2 class="mb-4 text-center">Login</h2>

            <?php if (isset($error)) echo "<div class='alert alert-danger text-center py-2'>$error</div>"; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" id="login-btn" class="btn btn-primary btn-lg shadow-sm">Login</button>
            </form>

            <p class="mt-4 text-center text-muted">
                Don’t have an account?
                <a href="register.php" class="btn btn-outline-primary btn-sm ms-1">Register</a>
            </p>

            <footer class="text-center mt-4 small">© 2025 Helpdesk. All rights reserved.</footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('login-btn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default form submission
            const btn = this;
            const form = btn.closest('form');

            // Change button to loading state
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
            btn.disabled = true;

            // Submit the form after a short delay to show animation
            setTimeout(() => {
                form.submit();
            }, 100);
        });
    </script>
</body>
</html>
