<?php
require_once __DIR__ . '/../includes/session_manager.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// 1. Check if ID is passed
if (!isset($_GET['id'])) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'User not found for editing.'];
    header('Location: manage_users.php');
    exit;
}

$user_id = $_GET['id'];

// 2. Get current user information from database
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Invalid user ID.'];
        header('Location: manage_users.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    header('Location: manage_users.php');
    exit;
}

// 3. Check if this is self-edit
$is_self = ($user['id'] == $_SESSION['user_id']);

// --- Display notification (if any from session) ---
$alert_message = null;
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); // Clear notification after retrieval
}

// START DISPLAYING INTERFACE
$page_title = "Edit User"; // Set title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Edit User: <?= htmlspecialchars($user['name']) ?></h1>
<p>Update user information. Leave password empty if you don't want to change it.</p>

<?php if (isset($alert_message)): ?>
    <div class="alert alert-<?= htmlspecialchars($alert_message['type']) ?>">
        <?= htmlspecialchars($alert_message['message']) ?>
    </div>
<?php endif; ?>

<div class="content-box">
    <form action="handlers/process_edit_user.php" method="POST">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="form-group">
            <label for="name">User Name</label>
            <input type="text" class="form-control" id="name" name="name" 
                   value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" 
                   value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="role">Role</label>
            <select class="form-control" id="role" name="role" <?= $is_self ? 'disabled' : '' ?>>
                <option value="student" <?= ($user['role'] == 'student') ? 'selected' : '' ?>>Student</option>
                <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : '' ?>>Administrator (Admin)</option>
            </select>
            
            <?php if ($is_self): ?>
                <input type="hidden" name="role" value="<?= htmlspecialchars($user['role']) ?>">
                <small><i>You cannot change your own role.</i></small>
            <?php endif; ?>
        </div>
        
        <hr style="margin: 1.5rem 0;">
        <p><strong>Change Password (Leave empty if not changing)</strong></p>
        
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        
        <div class="form-group">
            <label for="password2">Confirm New Password</label>
            <input type="password" class="form-control" id="password2" name="password2">
        </div>
        
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="manage_users.php" class="btn" style="background-color: #eee;">Cancel</a>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>