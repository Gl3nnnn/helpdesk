<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$logs = $pdo->query("SELECT a.*, u.username, t.title AS ticket_title FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id LEFT JOIN tickets t ON a.ticket_id = t.id ORDER BY a.timestamp DESC LIMIT 100")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>
<body data-user-role="<?php echo $user_role; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-system"></span> Audit Logs</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Ticket</th>
                        <th>Action</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['timestamp']; ?></td>
                        <td><?php echo htmlspecialchars($log['username'] ?: 'System'); ?></td>
                        <td><?php echo htmlspecialchars($log['ticket_title'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['old_value'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($log['new_value'] ?: 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
