<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark notifications as read if requested
if (isset($_POST['mark_read']) || isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$user_id]);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header("Location: notifications.php");
        exit;
    }
}

// Delete all notifications if requested
if (isset($_POST['delete_all'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header("Location: notifications.php");
        exit;
    }
}

// Mark individual notification as read
if (isset($_POST['mark_read_id'])) {
    $notif_id = $_POST['mark_read_id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Get notifications
$stmt = $pdo->prepare("SELECT n.*, t.created_at AS ticket_created_at, t.closed_at AS ticket_closed_at FROM notifications n LEFT JOIN tickets t ON n.ticket_id = t.id WHERE n.user_id = ? ORDER BY n.created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Count unread
$unread_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$unread_count->execute([$user_id]);
$unread_count = $unread_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-new_message"></span> Notifications</h2>
        <div class="mb-3 d-flex align-items-center gap-3">
            <?php if ($unread_count > 0): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_read" class="btn btn-primary">Mark All as Read</button>
                </form>
            <?php endif; ?>
            <?php if (!empty($notifications)): ?>
                <form method="POST" class="d-inline">
                    <button type="submit" name="delete_all" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all notifications?')">Delete All Notifications</button>
                </form>
            <?php endif; ?>
            <span class="badge bg-danger"><?php echo $unread_count; ?> Unread</span>
        </div>
        <?php if (empty($notifications)): ?>
            <p>No notifications yet.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notif): ?>
                    <div class="list-group-item <?php echo !$notif['is_read'] ? 'list-group-item-warning' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></h5>
                            <small><?php echo $notif['created_at']; ?></small>
                        </div>
                        <p class="mb-1"><span class="notification-icon <?php echo 'type-' . $notif['type']; ?>"></span>Type: <?php echo ucfirst(str_replace('_', ' ', $notif['type'])); ?></p>
                        <?php if ($notif['ticket_created_at']): ?>
                            <p class="mb-1">Ticket Created At: <?php echo $notif['ticket_created_at']; ?></p>
                        <?php endif; ?>
                        <?php if ($notif['ticket_closed_at']): ?>
                            <p class="mb-1">Ticket Closed At: <?php echo $notif['ticket_closed_at']; ?></p>
                        <?php endif; ?>
                <?php if ($notif['ticket_id']): ?>
                    <small><a href="<?php echo $_SESSION['role'] == 'admin' ? 'update_ticket.php' : 'view_tickets.php'; ?>?id=<?php echo $notif['ticket_id']; ?>" class="mark-read-link" data-notif-id="<?php echo $notif['id']; ?>">View Ticket</a></small>
                <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-fbbOQedDUMZZ5KreZpsbe1LCZPVmfTnH7ois6mU1QK+m14rQ1l2bGBq41eYeM/fS" crossorigin="anonymous"></script>
    <script>
        // Manual initialization of notification dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationDropdownMobile = document.getElementById('notificationDropdownMobile');
            if (notificationDropdown) {
                new bootstrap.Dropdown(notificationDropdown);
            }
            if (notificationDropdownMobile) {
                new bootstrap.Dropdown(notificationDropdownMobile);
            }
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
