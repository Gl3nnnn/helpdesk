<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Search and filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$agent_filter = $_GET['agent'] ?? '';
$department_filter = $_GET['department'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "SELECT t.*, u.username, a.username AS agent_name, d.name AS department_name FROM tickets t
          JOIN users u ON t.user_id = u.id
          LEFT JOIN users a ON t.assigned_to = a.id
          LEFT JOIN departments d ON t.department_id = d.id
          WHERE t.archived = 0";

$params = [];

if ($status_filter) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
}
if ($priority_filter) {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
}
if ($agent_filter) {
    $query .= " AND t.assigned_to = ?";
    $params[] = $agent_filter;
}
if ($department_filter) {
    $query .= " AND t.department_id = ?";
    $params[] = $department_filter;
}
if ($date_from) {
    $query .= " AND t.created_at >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND t.created_at <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="admin_tickets.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Description', 'Category', 'Priority', 'Status', 'Department', 'User', 'Assigned To', 'Created At', 'Closed At']);
    foreach ($tickets as $ticket) {
        fputcsv($output, [
            $ticket['id'],
            $ticket['title'],
            $ticket['description'],
            $ticket['category'],
            $ticket['priority'],
            $ticket['status'],
            $ticket['department_name'],
            $ticket['username'],
            $ticket['agent_name'] ?: 'Unassigned',
            $ticket['created_at'],
            $ticket['closed_at']
        ]);
    }
    fclose($output);
    exit;
}

$admins = $pdo->query("SELECT id, username FROM users WHERE role = 'admin'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>
<body data-user-role="<?php echo $user_role; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2>Admin Dashboard</h2>
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
                <select name="agent" class="form-select">
                    <option value="">All Agents</option>
                    <?php foreach ($admins as $admin): ?>
                    <option value="<?php echo $admin['id']; ?>" <?php if ($agent_filter == $admin['id']) echo 'selected'; ?>><?php echo htmlspecialchars($admin['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    <?php 
                    $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
                    foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php if ($department_filter == $dept['id']) echo 'selected'; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 filter-btn">Filter</button>
            </div>
        </form>
        <div class="mb-3">
            <a href="?export=1&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>&agent=<?php echo urlencode($agent_filter); ?>&department=<?php echo urlencode($department_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="btn btn-success export-btn">Export to CSV</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="id">ID</th>
                        <th class="title">Title</th>
                        <th class="user">User</th>
                        <th class="status">Status</th>
                        <th class="priority">Priority</th>
                        <th class="department">Department</th>
                        <th class="assigned">Assigned To</th>
                        <th class="created">Created At</th>
                        <th class="closed">Closed At</th>
                        <th class="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?php echo $ticket['id']; ?></td>
                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['username']); ?></td>
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
                        <td><?php echo $ticket['agent_name'] ? htmlspecialchars($ticket['agent_name']) : 'Unassigned'; ?></td>
                        <td><?php echo $ticket['created_at']; ?></td>
                        <td><?php echo $ticket['closed_at'] ?: 'N/A'; ?></td>
                        <td><a href="update_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary update-btn">Update</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
