<?php
session_start();
include 'db.php';
require_once 'csrf.php';

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Validate CSRF token for POST requests
check_csrf_token();

// Handle create new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['new_username']);
    $email = trim($_POST['new_email']);
    $password = $_POST['new_password'];
    $role = $_POST['new_role'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['user', 'admin'])) {
        $error = "Invalid role.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);
            $user_id = $pdo->lastInsertId();
            // Notify the new user
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'account_created', ?)");
            $stmt_notif->execute([$user_id, "Your account has been created by an administrator."]);
            $message = "✅ User created successfully.";
        } catch (PDOException $e) {
            $error = "User creation failed: " . $e->getMessage();
        }
    }
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'];
    $status = $_POST['edit_status'];

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } elseif (!in_array($role, ['user', 'admin'])) {
        $error = "Invalid role.";
    } elseif (!in_array($status, ['active', 'inactive'])) {
        $error = "Invalid status.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
        try {
            $stmt->execute([$username, $email, $role, $status, $user_id]);
            $message = "✅ User details updated.";
        } catch (PDOException $e) {
            $error = "Failed to update user: " . $e->getMessage();
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password_plain = bin2hex(random_bytes(4)); // random 8-char password
    $new_password_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_password_hash, $user_id]);
    // Notify the user about password reset
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'password_reset', ?)");
    $stmt_notif->execute([$user_id, "Your password has been reset by an administrator. New password: $new_password_plain"]);
    $message = "🔑 Password reset to '$new_password_plain' for user ID $user_id.";
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        // Fetch username for better message
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = "User not found.";
        } else {
            // Delete related notifications first to avoid foreign key constraint
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            try {
                $stmt->execute([$user_id]);
                $message = "User '" . htmlspecialchars($user['username']) . "' has been deleted.";
                $message_type = 'danger';
            } catch (PDOException $e) {
                $error = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
}

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">

</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2 class="mb-4"><span class="notification-icon type-new_ticket"></span> User Management</h2>

        <!-- Toast container -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="messageToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastMessage"></div>
            </div>
        </div>

        <!-- CREATE USER BUTTON -->
        <div class="mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">Create New User</button>
        </div>

        <!-- USER TABLE -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><span class="notification-icon type-new_ticket"></span> <?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge bg-<?php echo $user['role'] == 'admin' ? 'primary' : 'secondary'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="action-btns">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">Edit</button>
                                <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo $user['id']; ?>">Reset PW</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User - <?php echo htmlspecialchars($user['username']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label>Username</label>
                                                <input type="text" name="edit_username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-control" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Email</label>
                                                <input type="email" name="edit_email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Role</label>
                                                <select name="edit_role" class="form-select">
                                                    <option value="user" <?php if ($user['role']=='user') echo 'selected'; ?>>User</option>
                                                    <option value="admin" <?php if ($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label>Status</label>
                                                <select name="edit_status" class="form-select">
                                                    <option value="active" <?php if ($user['status']=='active') echo 'selected'; ?>>Active</option>
                                                    <option value="inactive" <?php if ($user['status']=='inactive') echo 'selected'; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Delete Confirmation Modals -->
        <?php foreach ($users as $user): ?>
        <?php if ($user['id'] != $_SESSION['user_id']): ?>
        <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                        <p class="text-muted">This action cannot be undone. All associated data will be permanently removed.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- Reset Password Modals -->
        <?php foreach ($users as $user): ?>
        <div class="modal fade" id="resetPasswordModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to reset the password for <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                        <p class="text-muted">A new random password will be generated and displayed after reset.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Create User Modal -->
        <div class="modal fade" id="createUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="new_username" class="form-label">Username</label>
                                <input type="text" name="new_username" id="new_username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_email" class="form-label">Email</label>
                                <input type="email" name="new_email" id="new_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_role" class="form-label">Role</label>
                                <select name="new_role" id="new_role" class="form-select" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="create_user" class="btn btn-success">Create User</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($message)): ?>
        const toast = new bootstrap.Toast(document.getElementById('messageToast'));
        document.getElementById('toastMessage').innerHTML = '<?php echo addslashes($message); ?>';
        toast.show();
    <?php endif; ?>
});
</script>
</body>
</html>
