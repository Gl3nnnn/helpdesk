<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$department_filter = $_GET['department'] ?? '';

$query = "SELECT t.*, u.username, d.name AS department_name FROM tickets t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN departments d ON t.department_id = d.id WHERE t.archived = 1";
$params = [];

if ($user_role == 'user') {
    $query .= " AND t.user_id = ?";
    $params[] = $user_id;
}

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

$query .= " ORDER BY t.closed_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="archived_tickets.csv"');
    $output = fopen('php://output', 'w');
    $headers = ['ID', 'Title', 'Status', 'Priority', 'Department', 'Created At', 'Closed At'];
    if ($user_role == 'admin') {
        array_splice($headers, 2, 0, ['User']);
    }
    fputcsv($output, $headers);
    foreach ($tickets as $ticket) {
        $row = [
            $ticket['id'],
            $ticket['title'],
            $ticket['status'],
            $ticket['priority'],
            $ticket['department_name'] ?: 'N/A',
            $ticket['created_at'],
            $ticket['closed_at']
        ];
        if ($user_role == 'admin') {
            array_splice($row, 2, 0, [$ticket['username'] ?? 'N/A']);
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Tickets - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="<?php echo $user_role; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-system"></span> Archived Tickets</h2>
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
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
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
        <div class="mb-3">
            <a href="?export=1&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>&department=<?php echo urlencode($department_filter); ?>" class="btn btn-success">Export to CSV</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <?php if ($user_role == 'admin'): ?>
                            <th>User</th>
                        <?php endif; ?>
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
                        <?php if ($user_role == 'admin'): ?>
                            <td><?php echo htmlspecialchars($ticket['username'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td><?php echo ucfirst($ticket['status']); ?></td>
                        <td><?php echo ucfirst($ticket['priority']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['department_name'] ?: 'N/A'); ?></td>
                        <td><?php echo $ticket['created_at']; ?></td>
                        <td><?php echo $ticket['closed_at']; ?></td>
                        <td><?php if ($user_role == 'admin'): ?><a href="update_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">View</a><?php else: ?><a href="view_tickets.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">View</a><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
