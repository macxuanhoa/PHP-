<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// TODO: 

// --- Get Subject list for dropdown ---
$subjects = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name ASC");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading subject list: " . $e->getMessage();
}

// --- Display notification (if any from session) ---
$alert_message = null;
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); // Clear notification after retrieval
}

// START DISPLAYING INTERFACE
$page_title = "Add New Post"; // Set title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Add New Post</h1>
<p>Compose and select subject for the post.</p>

<?php if (isset($alert_message)): ?>
    <div class="alert alert-<?= htmlspecialchars($alert_message['type']) ?>">
        <?= htmlspecialchars($alert_message['message']) ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content-box">
    <form action="handlers/process_add_post.php" method="POST">
        <div class="form-group">
            <label for="title">Post Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="subject_id">Subject</label>
            <select class="form-control" id="subject_id" name="subject_id" required>
                <option value="">— Select a subject —</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>">
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="content">Content</label>
            <textarea class="form-control" id="content" name="content" rows="10"></textarea>
            </div>
        
        <button type="submit" class="btn btn-primary">Post</button>
        <a href="manage_posts.php" class="btn" style="background-color: #eee;">Cancel</a>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>