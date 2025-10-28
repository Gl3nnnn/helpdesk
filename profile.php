<?php
session_start();
include 'db.php';
require_once 'csrf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate CSRF token for POST requests
check_csrf_token();
$errors = [];
$success = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch account statistics
$stats = $pdo->prepare("
    SELECT
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as resolved_tickets,
        AVG(CASE WHEN status = 'closed' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_hours
    FROM tickets
    WHERE user_id = ?
");
$stats->execute([$user_id]);
$user_stats = $stats->fetch();

// For admin users, also show resolved tickets as agent
if ($_SESSION['role'] == 'admin') {
    $agent_stats = $pdo->prepare("
        SELECT COUNT(*) as resolved_as_agent
        FROM tickets
        WHERE assigned_to = ? AND status = 'closed'
    ");
    $agent_stats->execute([$user_id]);
    $agent_data = $agent_stats->fetch();
    $user_stats['resolved_as_agent'] = $agent_data['resolved_as_agent'];
}

// Fetch recent activity (last 5 tickets)
$recent_tickets = $pdo->prepare("
    SELECT id, title, status, created_at
    FROM tickets
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_tickets->execute([$user_id]);
$recent_activity = $recent_tickets->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Handle password change
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $stored_password = $stmt->fetchColumn();

        if (!password_verify($current_password, $stored_password)) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $success = "Password changed successfully.";
        }
    } else {
        // Handle profile update
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }

        // Handle profile picture upload
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Only JPEG, PNG, and GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $errors[] = "File size must be less than 2MB.";
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
                $upload_path = 'uploads/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
                    if ($user['profile_picture'] && file_exists('uploads/' . $user['profile_picture'])) {
                        unlink('uploads/' . $user['profile_picture']);
                    }
                    $profile_picture = $filename;
                } else {
                    $errors[] = "Failed to upload profile picture.";
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$username, $email, $profile_picture, $user_id]);
            $success = "Profile updated successfully.";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="<?php echo htmlspecialchars($_SESSION['role']); ?>">
    <?php include 'navbar.php'; ?>
    <div class="profile-container">
        <div class="profile-header">
            <h2><span class="notification-icon type-user_profile"></span> Profile</h2>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Account Statistics - Full Width -->
        <div class="profile-section stats-section">
            <h3><span class="notification-icon type-system"></span> Account Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-value"><?php echo $user_stats['total_tickets']; ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $user_stats['resolved_as_agent']; ?></div>
                    <div class="stat-label">Resolved as Agent</div>
                </div>
                <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $user_stats['resolved_tickets']; ?></div>
                    <div class="stat-label">Resolved Tickets</div>
                </div>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'admin' && isset($user_stats['assigned_tickets'])): ?>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $user_stats['assigned_tickets']; ?></div>
                    <div class="stat-label">Assigned Tickets</div>
                </div>
                <?php endif; ?>
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-value"><?php echo $user_stats['avg_resolution_hours'] ? number_format($user_stats['avg_resolution_hours'], 1) . 'h' : 'N/A'; ?></div>
                    <div class="stat-label">Avg Resolution Time</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="profile-content-grid">
            <!-- Left Column -->
            <div class="profile-left-column">
                <!-- Profile Information -->
                <div class="profile-section">
                    <h3><span class="notification-icon type-user_profile"></span> Profile Information</h3>
                    <div class="mb-3">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <?php if (!empty($user['profile_picture'])): ?>
                            <div>
                                <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                        <span class="form-icon type-update"></span> Update Profile
                    </button>
                </div>
            </div>

            <!-- Right Column -->
            <div class="profile-right-column">
                <!-- Recent Activity -->
                <div class="profile-section">
                    <h3><span class="notification-icon type-new_ticket"></span> Recent Activity</h3>
                    <?php if (empty($recent_activity)): ?>
                        <p class="text-muted">No tickets submitted yet.</p>
                    <?php else: ?>
                        <div class="recent-activity-list">
                            <?php foreach ($recent_activity as $ticket): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <span class="ticket-id">#<?php echo $ticket['id']; ?></span>
                                        <span class="ticket-subject"><?php echo htmlspecialchars(substr($ticket['title'], 0, 50)); ?><?php echo strlen($ticket['title']) > 50 ? '...' : ''; ?></span>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span>
                                        <span class="activity-date"><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Password Change -->
                <div class="profile-section">
                    <h3><span class="notification-icon type-system"></span> Change Password</h3>
                    <p>Update your password to keep your account secure.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <span class="form-icon type-update"></span> Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" novalidate enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="modal_username" class="form-label"><span class="form-icon type-username"></span>Username</label>
                            <input type="text" class="form-control" id="modal_username" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="modal_email" class="form-label"><span class="form-icon type-email"></span>Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="modal_profile_picture" class="form-label"><span class="form-icon type-profile-picture"></span>Profile Picture</label>
                            <?php if (!empty($user['profile_picture'])): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Current Profile Picture" class="profile-picture-preview">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="modal_profile_picture" name="profile_picture" accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep current picture. Max 2MB, JPEG/PNG/GIF only.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><span class="form-icon type-update"></span>Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label for="modal_current_password" class="form-label"><span class="form-icon type-update"></span>Current Password</label>
                            <input type="password" class="form-control" id="modal_current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="modal_new_password" class="form-label"><span class="form-icon type-update"></span>New Password</label>
                            <input type="password" class="form-control" id="modal_new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="modal_confirm_password" class="form-label"><span class="form-icon type-update"></span>Confirm New Password</label>
                            <input type="password" class="form-control" id="modal_confirm_password" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><span class="form-icon type-update"></span>Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
