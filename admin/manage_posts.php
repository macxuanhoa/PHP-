<?php
require_once __DIR__ . '/../includes/session_manager.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// --- Handle DELETE logic (if any) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id_to_delete]);
        
        $_SESSION['alert_message'] = [
            'type' => 'success',
            'message' => 'Post deleted successfully!'
        ];
    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Error deleting post: ' . $e->getMessage()
        ];
    }
    header("Location: manage_posts.php");
    exit;
}

// --- Get list of posts (join 3 tables) ---
$posts = [];
try {
    $sql = "
        SELECT 
            p.id, p.title, p.created_at, 
            u.name as author_name, 
            s.name as subject_name
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN subjects s ON p.subject_id = s.id
        ORDER BY p.created_at DESC
    ";
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error querying database: " . $e->getMessage();
}

// --- Display notification (if any from session) ---
$alert_message = null;
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); // Delete notification after getting it
}

// START DISPLAYING INTERFACE
$page_title = "Manage Posts"; // Set title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1>Manage Posts</h1>
    <a href="add_post.php" class="btn btn-primary">Add New Post</a>
</div>
<p>List of all posts on the system.</p>

<?php if (isset($alert_message)): ?>
    <div class="alert alert-<?= htmlspecialchars($alert_message['type']) ?>">
        <?= htmlspecialchars($alert_message['message']) ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="content-box">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Subject</th>
                <th>Posted Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No posts yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= $post['id'] ?></td>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td><?= htmlspecialchars($post['author_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($post['subject_name'] ?? 'N/A') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></td>
                        <td class="action-links">
                            <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
                            <a href="manage_posts.php?action=delete&id=<?= $post['id'] ?>" 
                               class="delete"
                               onclick="return confirm('Are you sure you want to delete this post?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>