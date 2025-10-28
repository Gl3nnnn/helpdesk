<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$ticket_id = $_GET['id'] ?? null;
$ticket = null;
$messages = [];

if ($ticket_id) {
    // Show ticket details with chat, including department name and creator name
    $stmt = $pdo->prepare("SELECT t.*, d.name AS department_name, u.username AS creator_name FROM tickets t LEFT JOIN departments d ON t.department_id = d.id LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        header("Location: view_tickets.php");
        exit;
    }

    // Get messages
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_picture FROM messages m JOIN users u ON m.user_id = u.id WHERE ticket_id = ? ORDER BY timestamp ASC");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll();

    // Get attachments
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC");
    $stmt->execute([$ticket_id]);
    $attachments = $stmt->fetchAll();

    // Handle new message
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
        $message = trim($_POST['message']);
        if ($message) {
            $stmt = $pdo->prepare("INSERT INTO messages (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticket_id, $user_id, $message]);

            // Notify assigned admin or all admins if not assigned
            $assigned_admin_id = $ticket['assigned_to'];
            if ($assigned_admin_id && $assigned_admin_id != $user_id) {
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'new_message', ?, ?)");
                $stmt_notif->execute([$assigned_admin_id, $ticket_id, "New message on ticket #$ticket_id"]);
            } elseif (!$assigned_admin_id) {
                // Notify all admins if no assigned admin
                $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'top_admin')")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $admin_id) {
                    if ($admin_id != $user_id) {
                        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'new_message', ?, ?)");
                        $stmt_notif->execute([$admin_id, $ticket_id, "New message on ticket #$ticket_id"]);
                    }
                }
            }

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // AJAX request
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header("Location: view_tickets.php?id=$ticket_id");
                exit;
            }
        }
    }
} else {
    // Filter parameters
    $status_filter = $_GET['status'] ?? '';
    $priority_filter = $_GET['priority'] ?? '';
    $department_filter = $_GET['department'] ?? '';

$query = "SELECT t.*, d.name AS department_name FROM tickets t LEFT JOIN departments d ON t.department_id = d.id WHERE t.user_id = ? AND t.archived = 0";
    $params = [$user_id];

    if ($status_filter) {
        $query .= " AND t.status = ?";
        $params[] = $status_filter;
    }
    if ($priority_filter) {
        $query .= " AND t.priority = ?";
        $params[] = $priority_filter;
    }
    if ($department_filter) {
        $query .= " AND t.department_id = ?";
        $params[] = $department_filter;
    }

    $query .= " ORDER BY t.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // Export to CSV
    if (isset($_GET['export'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my_tickets.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Description', 'Category', 'Priority', 'Status', 'Department', 'Created At', 'Closed At']);
        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $ticket['id'],
                $ticket['title'],
                $ticket['description'],
                $ticket['category'],
                $ticket['priority'],
                $ticket['status'],
                $ticket['department_name'],
                $ticket['created_at'],
                $ticket['closed_at']
            ]);
        }
        fclose($output);
        exit;
    }
}

$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Tickets - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>
<body data-user-role="<?php echo $user_role; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <?php if ($ticket): ?>
            <h2>Ticket #<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['title']); ?></h2>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['description']); ?></p>
            <p><strong>Status:</strong> <span class="badge status-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span></p>
            <p><strong>Priority:</strong> <span class="badge priority-<?php echo $ticket['priority']; ?>"><?php echo ucfirst($ticket['priority']); ?></span></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($ticket['department_name'] ?: 'N/A'); ?></p>
            <p><strong>Created By:</strong> <?php echo htmlspecialchars($ticket['creator_name']); ?></p>
            <p><strong>Created At:</strong> <?php echo $ticket['created_at']; ?></p>
            <a href="view_tickets.php" class="btn btn-secondary mb-3">Back to My Tickets</a>

            <?php if (!empty($attachments)): ?>
            <h3>Attachments</h3>
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

            <h3>Messages</h3>
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
                                <span class="user-icon"></span><strong><?php echo htmlspecialchars($msg['username']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?>
                                <small class="text-muted"> (<?php echo $msg['timestamp']; ?>)</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="POST" id="message-form">
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        <?php else: ?>
            <h2><span class="notification-icon type-new_ticket"></span> My Tickets</h2>
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="open" <?php if ($status_filter == 'open') echo 'selected'; ?>>Open</option>
                        <option value="assigned" <?php if ($status_filter == 'assigned') echo 'selected'; ?>>Assigned</option>
                        <option value="closed" <?php if ($status_filter == 'closed') echo 'selected'; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <option value="low" <?php if ($priority_filter == 'low') echo 'selected'; ?>>Low</option>
                        <option value="medium" <?php if ($priority_filter == 'medium') echo 'selected'; ?>>Medium</option>
                        <option value="high" <?php if ($priority_filter == 'high') echo 'selected'; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php if ($department_filter == $dept['id']) echo 'selected'; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><span class="filter-icon"></span> Filter</button>
                </div>
                <div class="col-md-4">
                    <a href="?export=1&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>&department=<?php echo urlencode($department_filter); ?>" class="btn btn-success"><span class="export-icon"></span> Export to CSV</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Department</th>
                            <th>Created At</th>
                            <th>Closed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo $ticket['id']; ?></td>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($ticket['status']) {
                                    case 'open': $status_class = 'bg-success'; break;
                                    case 'assigned': $status_class = 'bg-primary'; break;
                                    case 'closed': $status_class = 'bg-danger'; break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?> status-<?php echo $ticket['status']; ?>"><?php echo ucfirst($ticket['status']); ?></span>
                            </td>
                            <td>
                                <?php
                                $priority_class = '';
                                switch ($ticket['priority']) {
                                    case 'low': $priority_class = 'bg-success'; break;
                                    case 'medium': $priority_class = 'bg-warning'; break;
                                    case 'high': $priority_class = 'bg-danger'; break;
                                }
                                ?>
                                <span class="badge <?php echo $priority_class; ?> priority-<?php echo $ticket['priority']; ?>"><?php echo ucfirst($ticket['priority']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($ticket['department_name'] ?: 'N/A'); ?></td>
                            <td><?php echo $ticket['created_at']; ?></td>
                            <td><?php echo $ticket['closed_at'] ?: 'N/A'; ?></td>
                            <td><a href="?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info"><span class="view-chat-icon"></span> View Chat</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>

    <!-- Modal for image preview -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
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
