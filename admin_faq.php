<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_faq'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        if ($question && $answer) {
            $stmt = $pdo->prepare("INSERT INTO faq (question, answer, category, author_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$question, $answer, $category ?: null, $_SESSION['user_id']]);
            header("Location: admin_faq.php");
            exit;
        }
    } elseif (isset($_POST['edit_faq'])) {
        $id = $_POST['id'];
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        $category = trim($_POST['category']);
        if ($question && $answer) {
            $stmt = $pdo->prepare("UPDATE faq SET question = ?, answer = ?, category = ? WHERE id = ?");
            $stmt->execute([$question, $answer, $category ?: null, $id]);
            header("Location: admin_faq.php");
            exit;
        }
    } elseif (isset($_POST['delete_faq'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM faq WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_faq.php");
        exit;
    }
}

// Get all FAQs
$faqs = $pdo->query("SELECT f.*, u.username AS author FROM faq f LEFT JOIN users u ON f.author_id = u.id ORDER BY f.created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage FAQ - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-user-role="admin">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2>Manage FAQ</h2>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addFaqModal">Add New FAQ</button>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faqs as $faq): ?>
                    <tr>
                        <td><?php echo $faq['id']; ?></td>
                        <td><?php echo htmlspecialchars($faq['question']); ?></td>
                        <td><?php echo htmlspecialchars($faq['category'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($faq['author'] ?: 'N/A'); ?></td>
                        <td><?php echo $faq['created_at']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editFaqModal" onclick="editFaq(<?php echo $faq['id']; ?>, '<?php echo addslashes($faq['question']); ?>', '<?php echo addslashes($faq['answer']); ?>', '<?php echo addslashes($faq['category']); ?>')">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                                <button type="submit" name="delete_faq" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this FAQ?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add FAQ Modal -->
    <div class="modal fade" id="addFaqModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="question" class="form-label">Question</label>
                            <input type="text" class="form-control" id="question" name="question" required>
                        </div>
                        <div class="mb-3">
                            <label for="answer" class="form-label">Answer</label>
                            <textarea class="form-control" id="answer" name="answer" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_faq" class="btn btn-primary">Add FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div class="modal fade" id="editFaqModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="mb-3">
                            <label for="editQuestion" class="form-label">Question</label>
                            <input type="text" class="form-control" id="editQuestion" name="question" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAnswer" class="form-label">Answer</label>
                            <textarea class="form-control" id="editAnswer" name="answer" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <input type="text" class="form-control" id="editCategory" name="category">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_faq" class="btn btn-primary">Update FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        function editFaq(id, question, answer, category) {
            document.getElementById('editId').value = id;
            document.getElementById('editQuestion').value = question;
            document.getElementById('editAnswer').value = answer;
            document.getElementById('editCategory').value = category;
        }
    </script>
</body>
</html>
