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
    
    // (Improvement) Don't allow admin to delete themselves
    if ($id_to_delete == $_SESSION['user_id']) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'You cannot delete your own account.'
        ];
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            
            $_SESSION['alert_message'] = [
                'type' => 'success',
                'message' => 'User deleted successfully!'
            ];
        } catch (PDOException $e) {
            $_SESSION['alert_message'] = [
                'type' => 'danger',
                'message' => 'Error deleting user: ' . $e->getMessage()
            ];
        }
    }
    header("Location: manage_users.php");
    exit;
}

// --- Get list of users ---
$users = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
$page_title = "Manage Users"; // (NEW) Set page title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <a href="add_user.php" class="btn btn-primary">Add New User</a>
</div>
<p>Display list of all users in the system.</p>

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
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Join Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="role-<?= $user['role'] ?>">
                                <?= ($user['role'] == 'admin') ? 'Administrator' : 'Student' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td class="action-links">
                            <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a> 
                            
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="manage_users.php?action=delete&id=<?= $user['id'] ?>" 
                                   class="delete" 
                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                   Delete
                                </a>
                            <?php endif; ?>
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