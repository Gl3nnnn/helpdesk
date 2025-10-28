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

// Handle category add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }
}

// Handle priority add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_priority'])) {
    $name = trim($_POST['priority_name']);
    $level = (int)$_POST['priority_level'];
    if ($name && $level > 0) {
        $stmt = $pdo->prepare("INSERT INTO priorities (name, level) VALUES (?, ?)");
        $stmt->execute([$name, $level]);
    }
}

// Handle SLA add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sla'])) {
    $category_id = (int)$_POST['sla_category'];
    $priority_id = (int)$_POST['sla_priority'];
    $response = (int)$_POST['response_time'];
    $resolution = (int)$_POST['resolution_time'];
    if ($category_id && $priority_id && $response > 0 && $resolution > 0) {
        $stmt = $pdo->prepare("INSERT INTO sla_rules (category_id, priority_id, response_time_hours, resolution_time_hours) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category_id, $priority_id, $response, $resolution]);
    }
}

// Handle category delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $id = (int)$_POST['category_id'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Handle priority delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_priority'])) {
    $id = (int)$_POST['priority_id'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM priorities WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Handle SLA delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_sla'])) {
    $id = (int)$_POST['sla_id'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM sla_rules WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Handle department add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['department_name']);
    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO departments (name) VALUES (?)");
        $stmt->execute([$name]);
    }
}

// Handle department delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_department'])) {
    $id = (int)$_POST['department_id'];
    if ($id > 0) {
        // Check if department is used in active (non-archived) tickets
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE department_id = ? AND archived = 0");
        $stmt_check->execute([$id]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $error = "Cannot delete department: it is associated with $count active ticket(s). Please archive or reassign tickets first.";
        } else {
            // Set department_id to NULL for archived tickets
            $stmt_update = $pdo->prepare("UPDATE tickets SET department_id = NULL WHERE department_id = ? AND archived = 1");
            $stmt_update->execute([$id]);

            // Now delete the department
            $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$priorities = $pdo->query("SELECT * FROM priorities ORDER BY level")->fetchAll();
$sla_rules = $pdo->query("SELECT s.*, c.name AS category_name, p.name AS priority_name FROM sla_rules s JOIN categories c ON s.category_id = c.id JOIN priorities p ON s.priority_id = p.id ORDER BY c.name, p.level")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2><span class="notification-icon type-system"></span> Admin Settings</h2>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <span class="notification-icon type-system"></span> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <h3>Categories</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="mb-3">
                        <input type="text" name="category_name" class="form-control" placeholder="New Category" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($categories as $cat): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="notification-icon type-new_message"></span> <?php echo htmlspecialchars($cat['name']); ?></span>
                        <form method="POST" class="d-inline" id="delete-category-<?php echo $cat['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                            <button type="button" name="delete_category" class="btn btn-danger btn-sm delete-btn" data-form-id="delete-category-<?php echo $cat['id']; ?>" data-item-name="<?php echo htmlspecialchars($cat['name']); ?>" data-item-type="category">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-md-3">
                <h3>Priorities</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="mb-3">
                        <input type="text" name="priority_name" class="form-control" placeholder="Priority Name" required>
                        <input type="number" name="priority_level" class="form-control mt-2" placeholder="Level (1-10)" min="1" max="10" required>
                    </div>
                    <button type="submit" name="add_priority" class="btn btn-primary">Add Priority</button>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($priorities as $pri): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="notification-icon type-ticket_resolution"></span> <?php echo htmlspecialchars($pri['name']) . ' (Level ' . $pri['level'] . ')'; ?></span>
                        <form method="POST" class="d-inline" id="delete-priority-<?php echo $pri['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="priority_id" value="<?php echo $pri['id']; ?>">
                            <button type="button" name="delete_priority" class="btn btn-danger btn-sm delete-btn" data-form-id="delete-priority-<?php echo $pri['id']; ?>" data-item-name="<?php echo htmlspecialchars($pri['name']); ?>" data-item-type="priority">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-md-3">
                <h3>Departments</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="mb-3">
                        <input type="text" name="department_name" class="form-control" placeholder="New Department" required>
                    </div>
                    <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($departments as $dept): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="notification-icon type-new_ticket"></span> <?php echo htmlspecialchars($dept['name']); ?></span>
                        <form method="POST" class="d-inline" id="delete-department-<?php echo $dept['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                            <button type="button" name="delete_department" class="btn btn-danger btn-sm delete-btn" data-form-id="delete-department-<?php echo $dept['id']; ?>" data-item-name="<?php echo htmlspecialchars($dept['name']); ?>" data-item-type="department">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="col-md-3">
                <h3>SLA Rules</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="mb-3">
                        <select name="sla_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="sla_priority" class="form-select mt-2" required>
                            <option value="">Select Priority</option>
                            <?php foreach ($priorities as $pri): ?>
                            <option value="<?php echo $pri['id']; ?>"><?php echo htmlspecialchars($pri['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="response_time" class="form-control mt-2" placeholder="Response Time (hours)" min="1" required>
                        <input type="number" name="resolution_time" class="form-control mt-2" placeholder="Resolution Time (hours)" min="1" required>
                    </div>
                    <button type="submit" name="add_sla" class="btn btn-primary">Add SLA Rule</button>
                </form>
                <ul class="list-group mt-3">
                    <?php foreach ($sla_rules as $sla): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="notification-icon type-system"></span> <?php echo htmlspecialchars($sla['category_name']) . ' - ' . htmlspecialchars($sla['priority_name']) . ': Response ' . $sla['response_time_hours'] . 'h, Resolution ' . $sla['resolution_time_hours'] . 'h'; ?></span>
                        <form method="POST" class="d-inline" id="delete-sla-<?php echo $sla['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="sla_id" value="<?php echo $sla['id']; ?>">
                            <button type="button" name="delete_sla" class="btn btn-danger btn-sm delete-btn" data-form-id="delete-sla-<?php echo $sla['id']; ?>" data-item-name="<?php echo htmlspecialchars($sla['category_name'] . ' - ' . $sla['priority_name']); ?>" data-item-type="SLA rule">Delete</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this <span id="item-type"></span>?</p>
                    <p><strong>Item:</strong> <span id="item-name"></span></p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Handle delete button clicks
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const itemTypeSpan = document.getElementById('item-type');
            const itemNameSpan = document.getElementById('item-name');
            let currentFormId = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const formId = this.getAttribute('data-form-id');
                    const itemName = this.getAttribute('data-item-name');
                    const itemType = this.getAttribute('data-item-type');

                    itemTypeSpan.textContent = itemType;
                    itemNameSpan.textContent = itemName;
                    currentFormId = formId;

                    deleteModal.show();
                });
            });

            confirmDeleteBtn.addEventListener('click', function() {
                if (currentFormId) {
                    const form = document.getElementById(currentFormId);
                    if (form) {
                        // Add the delete action to the form
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = form.querySelector('button').name;
                        deleteInput.value = '1';
                        form.appendChild(deleteInput);

                        form.submit();
                    }
                }
                deleteModal.hide();
            });
        });
    </script>
</body>
</html>
