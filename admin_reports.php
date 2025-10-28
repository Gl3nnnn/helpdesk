<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Tickets per category
$category_stats = $pdo->query("SELECT category, COUNT(*) as count FROM tickets GROUP BY category ORDER BY count DESC")->fetchAll();

// Avg resolution time (in hours)
$resolution_stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_time FROM tickets WHERE status = 'closed' AND closed_at IS NOT NULL");
$resolution_stmt->execute();
$avg_resolution = $resolution_stmt->fetch()['avg_time'];

// Agent performance
$agent_stats = $pdo->query("SELECT u.username, COUNT(t.id) as closed_tickets FROM users u LEFT JOIN tickets t ON u.id = t.assigned_to AND t.status = 'closed' WHERE u.role = 'admin' GROUP BY u.id ORDER BY closed_tickets DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-system"></span> Admin Reports</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Average Resolution Time</div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $avg_resolution ? round($avg_resolution, 2) . ' hours' : 'N/A'; ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <h3>Tickets per Category</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_stats as $stat): ?>
                            <tr>
                                <td><span class="notification-icon type-new_message"></span> <?php echo htmlspecialchars($stat['category'] ?: 'Uncategorized'); ?></td>
                                <td><?php echo $stat['count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h3>Agent Performance</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Closed Tickets</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agent_stats as $stat): ?>
                            <tr>
                                <td><span class="notification-icon type-new_ticket"></span> <?php echo htmlspecialchars($stat['username']); ?></td>
                                <td><?php echo $stat['closed_tickets']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
