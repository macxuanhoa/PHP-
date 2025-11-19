<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// --- Handle DELETE logic (if any) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    try {
        // Check if subject has any posts before deleting
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE subject_id = ?");
        $check_stmt->execute([$id_to_delete]);
        $post_count = $check_stmt->fetchColumn();
        
        if ($post_count > 0) {
            $_SESSION['alert_message'] = [
                'type' => 'danger',
                'message' => "Cannot delete subject - it has {$post_count} post(s) associated with it."
            ];
        } else {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            
            $_SESSION['alert_message'] = [
                'type' => 'success',
                'message' => 'Subject deleted successfully!'
            ];
        }
    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Error deleting subject: ' . $e->getMessage()
        ];
    }
    // Return to this page (remove action parameters)
    header("Location: manage_subjects.php");
    exit;
}

// --- Get list of subjects ---
$subjects = [];
try {
    $sql = "
        SELECT 
            s.id, s.name, s.description, COUNT(p.id) as post_count
        FROM subjects s
        LEFT JOIN posts p ON s.id = p.subject_id
        GROUP BY s.id, s.name, s.description
        ORDER BY s.name ASC
    ";
    $stmt = $pdo->query($sql);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1>Manage Subjects</h1>
    <a href="add_subject.php" class="btn btn-primary">Add New Subject</a>
</div>
<p>List of subjects in the system.</p>

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
                <th>Subject Name</th>
                <th>Description</th>
                <th>Number of Posts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subjects)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No subjects yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= $subject['id'] ?></td>
                        <td><strong><?= htmlspecialchars($subject['name']) ?></strong></td>
                        <td><?= htmlspecialchars($subject['description'] ?? 'No description') ?></td>
                        <td><?= $subject['post_count'] ?></td>
                        <td class="action-links">
                            <a href="edit_subject.php?id=<?= $subject['id'] ?>">Edit</a>
                            
                            <a href="manage_subjects.php?action=delete&id=<?= $subject['id'] ?>" 
                               class="delete" 
                               onclick="return confirm('Are you sure you want to delete this subject?');">
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