<?php
// create_ticket.php - Week 3: Create support ticket
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($description)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($title) < 5) {
        $error = "Title is too short.";
    } else {
        // Insert ticket - Week 3
        $stmt = $pdo->prepare(
            "INSERT INTO tickets (title, description, status, user_id) VALUES (?, ?, 'Pending', ?)"
        );
        $stmt->execute([$title, $description, $_SESSION['user_id']]);

        $success = "Ticket submitted successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Ticket - CTISMS v1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">CTISMS v1</a>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="card">
                <div class="card-header">Submit a Support Ticket</div>
                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                            <a href="dashboard.php">Go to Dashboard</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Issue Title</label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="Brief summary of your problem"
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="5"
                                      placeholder="Describe your issue in detail..."
                                      required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Note: priority field planned for later weeks -->

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Submit Ticket</button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
