<?php
session_start();
include 'db.php';

// Search functionality
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT * FROM faq WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (question LIKE ? OR answer LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($category_filter) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$faqs = $stmt->fetchAll();

// Get unique categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM faq WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<?php $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?>
<body data-user-role="<?php echo $user_role; ?>">
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h2>Frequently Asked Questions</h2>
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search FAQs..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($category_filter == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
        <div class="accordion" id="faqAccordion">
            <?php if (empty($faqs)): ?>
                <p>No FAQs found matching your criteria.</p>
            <?php else: ?>
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                            <button class="accordion-button <?php if ($index > 0) echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index == 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <?php if ($faq['category']): ?>
                                    <small class="text-muted ms-2">(<?php echo htmlspecialchars($faq['category']); ?>)</small>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php if ($index == 0) echo 'show'; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
