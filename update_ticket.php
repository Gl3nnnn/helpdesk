<?php
session_start();
include 'db.php';
require_once 'csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Validate CSRF token for POST requests
check_csrf_token();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) {
    header("Location: admin_dashboard.php");
    exit;
}

$admins = $pdo->query("SELECT id, username FROM users WHERE role IN ('admin', 'top_admin')")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

// Get messages
$messages = $pdo->prepare("SELECT m.*, u.username, u.profile_picture FROM messages m JOIN users u ON m.user_id = u.id WHERE ticket_id = ? ORDER BY timestamp ASC");
$messages->execute([$id]);
$messages = $messages->fetchAll();

// Get attachments
$attachments = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC");
$attachments->execute([$id]);
$attachments = $attachments->fetchAll();

    // Handle new message
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
        $message = trim($_POST['message']);
        if ($message) {
            $stmt_msg = $pdo->prepare("INSERT INTO messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt_msg->execute([$id, $_SESSION['user_id'], $message]);

            // Notify assigned admin and ticket owner of new message
            $ticket_owner_id = $ticket['user_id'];
            $assigned_admin_id = $ticket['assigned_to'];

            $notified_users = [];

            if ($assigned_admin_id && $assigned_admin_id != $_SESSION['user_id']) {
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'new_message', ?, ?)");
                $stmt_notif->execute([$assigned_admin_id, $id, "New message on ticket #$id"]);
                $notified_users[] = $assigned_admin_id;
            }

            if ($ticket_owner_id != $_SESSION['user_id'] && !in_array($ticket_owner_id, $notified_users)) {
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'new_message', ?, ?)");
                $stmt_notif->execute([$ticket_owner_id, $id, "New message on your ticket #$id"]);
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // AJAX request
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header("Location: update_ticket.php?id=$id");
                exit;
            }
        }
    }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'] ?: null;
    $department_id = $_POST['department_id'];

    // Log changes
    if ($status != $ticket['status']) {
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (ticket_id, user_id, action, old_value, new_value) VALUES (?, ?, 'Status Change', ?, ?)");
        $stmt_log->execute([$id, $_SESSION['user_id'], $ticket['status'], $status]);
    }
    if ($assigned_to != $ticket['assigned_to']) {
        $old_agent = $ticket['assigned_to'] ? $pdo->query("SELECT username FROM users WHERE id = " . $ticket['assigned_to'])->fetch()['username'] : 'Unassigned';
        $new_agent = $assigned_to ? $pdo->query("SELECT username FROM users WHERE id = $assigned_to")->fetch()['username'] : 'Unassigned';
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (ticket_id, user_id, action, old_value, new_value) VALUES (?, ?, 'Assignment Change', ?, ?)");
        $stmt_log->execute([$id, $_SESSION['user_id'], $old_agent, $new_agent]);
    }
    if ($department_id != $ticket['department_id']) {
        $old_dept = $pdo->query("SELECT name FROM departments WHERE id = " . $ticket['department_id'])->fetch()['name'];
        $new_dept = $pdo->query("SELECT name FROM departments WHERE id = $department_id")->fetch()['name'];
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (ticket_id, user_id, action, old_value, new_value) VALUES (?, ?, 'Department Change', ?, ?)");
        $stmt_log->execute([$id, $_SESSION['user_id'], $old_dept, $new_dept]);
    }

    $update_fields = "status = ?, assigned_to = ?, department_id = ?";
    $params = [$status, $assigned_to, $department_id];

    if ($status == 'closed') {
        $update_fields .= ", closed_at = NOW(), archived = 1";
    } elseif ($ticket['status'] == 'closed' && $status != 'closed') {
        $update_fields .= ", closed_at = NULL, archived = 0";
    }

    $stmt = $pdo->prepare("UPDATE tickets SET $update_fields WHERE id = ?");
    $params[] = $id;
    $stmt->execute($params);

    // Notifications
    if ($status == 'closed' && $ticket['status'] != 'closed') {
        // Ticket resolution
        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'ticket_resolution', ?, ?)");
        $stmt_notif->execute([$ticket['user_id'], $id, "Your ticket #$id has been resolved"]);
    } elseif ($status != $ticket['status'] || $assigned_to != $ticket['assigned_to']) {
        // Ticket update
        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'ticket_update', ?, ?)");
        $stmt_notif->execute([$ticket['user_id'], $id, "Your ticket #$id has been updated"]);
    }

    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Ticket - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>

    <div class="container mt-5">
        <div id="toastContainer" style="position: fixed; top: 1rem; left: 50%; transform: translateX(-50%); z-index: 1080;"></div>
        <h2><span class="notification-icon type-ticket_update"></span> Update Ticket #<?php echo $ticket['id']; ?></h2>
        <p><strong>Title:</strong> <?php echo htmlspecialchars($ticket['title']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['description']); ?></p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="open" <?php if ($ticket['status'] == 'open') echo 'selected'; ?>>Open</option>
                    <option value="assigned" <?php if ($ticket['status'] == 'assigned') echo 'selected'; ?>>Assigned</option>
                    <option value="closed" <?php if ($ticket['status'] == 'closed') echo 'selected'; ?>>Closed</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Assign to Admin</label>
                <select name="assigned_to" class="form-control">
                    <option value="">Unassigned</option>
                    <?php foreach ($admins as $admin): ?>
                    <option value="<?php echo $admin['id']; ?>" <?php if ($ticket['assigned_to'] == $admin['id']) echo 'selected'; ?>><?php echo htmlspecialchars($admin['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php if ($ticket['department_id'] == $dept['id']) echo 'selected'; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Ticket</button>
        </form>

        <h3><span class="attachment-icon"></span> Attachments</h3>
        <?php if (!empty($attachments)): ?>
        <ul class="list-group mb-3">
            <?php foreach ($attachments as $attachment): ?>
            <li class="list-group-item">
                <?php
                $ext = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                $pdf_extensions = ['pdf'];
                if (in_array($ext, $image_extensions)):
                ?>
                    <a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" class="attachment-preview" data-type="image" data-filename="<?php echo htmlspecialchars($attachment['filename']); ?>" style="display: inline-block; max-width: 150px; max-height: 150px; margin-right: 10px;">
                        <img src="<?php echo htmlspecialchars($attachment['filepath']); ?>" alt="<?php echo htmlspecialchars($attachment['filename']); ?>" style="max-width: 150px; max-height: 150px; object-fit: contain; border: 1px solid #ccc; padding: 2px; border-radius: 4px;">
                    </a>
                    <a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" download class="btn btn-sm btn-outline-primary align-middle">Download</a>
                    <br>
                    <small><?php echo htmlspecialchars($attachment['filename']); ?></small>
                <?php elseif (in_array($ext, $pdf_extensions)): ?>
                    <a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" class="attachment-preview" data-type="pdf" data-filename="<?php echo htmlspecialchars($attachment['filename']); ?>" style="display: inline-block; margin-right: 10px;">
                        <div style="width: 150px; height: 150px; border: 1px solid #ccc; padding: 2px; border-radius: 4px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                            <span>PDF</span>
                        </div>
                    </a>
                    <a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" download class="btn btn-sm btn-outline-primary align-middle">Download</a>
                    <br>
                    <small><?php echo htmlspecialchars($attachment['filename']); ?></small>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" target="_blank" download><?php echo htmlspecialchars($attachment['filename']); ?></a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>No attachments found.</p>
        <?php endif; ?>

        <h3><span class="message-icon"></span> Messages</h3>
        <button id="refresh-btn" type="button" class="btn btn-secondary mb-2" onclick="window.location.reload();">Refresh Messages</button>
        <div class="chat-box mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
            <?php if (empty($messages)): ?>
                <p>No messages yet.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message mb-2 d-flex align-items-start">
                        <div class="me-2">
                            <?php if (!empty($msg['profile_picture'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($msg['profile_picture']); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #ccc; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff;">
                                    <?php echo strtoupper(substr($msg['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($msg['username']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?>
                            <small class="text-muted"> (<?php echo $msg['timestamp']; ?>)</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="POST" id="message-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
                <textarea name="message" class="form-control" rows="3" placeholder="Type your message..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmLogout">Logout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for attachment preview -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imagePreviewModalLabel">Attachment Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <div id="previewContent" style="max-height: 70vh; overflow: auto;">
              <!-- Content will be dynamically loaded here -->
            </div>
          </div>
          <div class="modal-footer">
            <a id="downloadImageBtn" href="#" download class="btn btn-primary">Download</a>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="script.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        var previewContent = document.getElementById('previewContent');
        var downloadImageBtn = document.getElementById('downloadImageBtn');

        document.querySelectorAll('.attachment-preview').forEach(function (link) {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            var fileUrl = this.getAttribute('href');
            var filename = this.getAttribute('data-filename');
            var type = this.getAttribute('data-type');

            previewContent.innerHTML = ''; // Clear previous content

            if (type === 'image') {
              var img = document.createElement('img');
              img.src = fileUrl;
              img.alt = 'Preview';
              img.style.maxWidth = '100%';
              img.style.maxHeight = '70vh';
              img.style.objectFit = 'contain';
              previewContent.appendChild(img);
            } else if (type === 'pdf') {
              var iframe = document.createElement('iframe');
              iframe.src = fileUrl;
              iframe.style.width = '100%';
              iframe.style.height = '70vh';
              iframe.style.border = 'none';
              previewContent.appendChild(iframe);
            }

            downloadImageBtn.href = fileUrl;
            downloadImageBtn.setAttribute('download', filename);
            imagePreviewModal.show();
          });
        });
      });
    </script>
</body>
</html>
