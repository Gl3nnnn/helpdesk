<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

if (isset($_GET['backup'])) {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = "c:/xampp/mysql/bin/mysqldump.exe -u root helpdesk > $backup_file";
    exec($command, $output, $return_var);
    if ($return_var == 0) {
        $message = "Backup created: $backup_file";
    } else {
        $message = "Backup failed.";
    }
}

// Restore functionality (basic, assumes file in same directory)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['restore_file'])) {
    $file = $_FILES['restore_file']['tmp_name'];
    if ($file) {
        $command = "c:/xampp/mysql/bin/mysql.exe -u root helpdesk < $file";
        exec($command, $output, $return_var);
        if ($return_var == 0) {
            $message = "Restore completed.";
        } else {
            $message = "Restore failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Restore - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2>Backup & Restore Database</h2>
        <?php if ($message) echo "<div class='alert alert-info'>$message</div>"; ?>
        <div class="row">
            <div class="col-md-6">
                <h3>Backup</h3>
                <p>Click to create a backup of the database.</p>
                <a href="?backup=1" class="btn btn-primary">Create Backup</a>
            </div>
            <div class="col-md-6">
                <h3>Restore</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="restore_file" class="form-control" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('This will overwrite the database. Proceed?')">Restore from File</button>
                </form>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
