<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$ticket_id = $_GET['ticket_id'] ?? null;
if (!$ticket_id) {
    http_response_code(400);
    exit;
}

// Check if user owns the ticket or is admin
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role != 'admin') {
    $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_picture FROM messages m JOIN users u ON m.user_id = u.id WHERE ticket_id = ? ORDER BY timestamp ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
?>
