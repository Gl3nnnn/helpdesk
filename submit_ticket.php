<?php
try {
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
} catch (Exception $e) {
    die("Error: Failed to initialize session or database connection. " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $department_id = $_POST['department_id'] ?? null;

    try {
        // Insert ticket
        $stmt = $pdo->prepare("INSERT INTO tickets (title, description, category, priority, user_id, department_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $category, $priority, $user_id, $department_id]);
        $ticket_id = $pdo->lastInsertId();

        // Notify all admins of new ticket
        $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'top_admin')")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $admin_id) {
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, ticket_id, message) VALUES (?, 'new_ticket', ?, ?)");
            $stmt_notif->execute([$admin_id, $ticket_id, "New ticket submitted: #$ticket_id - $title"]);
        }

        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';

            // Check if upload directory exists, create if not
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = basename($_FILES['attachment']['name']);
            $targetFilePath = $uploadDir . $filename;

            // Validate file size (max 40 MB)
            $maxFileSize = 40 * 1024 * 1024; // 40 MB
            if ($_FILES['attachment']['size'] > $maxFileSize) {
                die("Error: File size exceeds 40 MB limit.");
            }

            // Validate allowed file types
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) {
                die("Error: Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
            }

            // Move uploaded file
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
                // Insert attachment record
                $stmt = $pdo->prepare("INSERT INTO attachments (ticket_id, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$ticket_id, $filename, 'uploads/' . $filename, $user_id]);
            } else {
                die("Error: Failed to move uploaded file.");
            }
        }

        // Check if request is AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => 'Ticket submitted successfully.', 'ticket_id' => $ticket_id]);
            exit;
        } else {
            header("Location: view_tickets.php?id=$ticket_id");
            exit;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch categories and departments for form
$categories = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT id, name FROM departments")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="<?php echo $_SESSION['role']; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2>Submit a New Ticket</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-3">
                <label for="title" class="form-label"><span style="margin-right: 6px;">📝</span>Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label"><span style="margin-right: 6px;">🗒️</span>Description</label>
                <textarea name="description" id="description" class="form-control" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label"><span style="margin-right: 6px;">📂</span>Category</label>
                <select name="category" id="category" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label"><span style="margin-right: 6px;">⚡</span>Priority</label>
                <select name="priority" id="priority" class="form-select" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="department_id" class="form-label"><span style="margin-right: 6px;">🏢</span>Department</label>
                <select name="department_id" id="department_id" class="form-select">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="attachment" class="form-label"><span style="margin-right: 6px;">📎</span>Attachment</label>
                <input type="file" name="attachment" id="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt">
            </div>
            <button type="submit" class="btn btn-primary"><span style="margin-right: 6px;">🚀</span>Submit Ticket</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
