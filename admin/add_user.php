<?php
require_once __DIR__ . '/../includes/session_manager.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// --- Display notification (if any from session) ---
$alert_message = null;
if (isset($_SESSION['alert_message'])) {
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_message']); // Clear notification after retrieval
}

// START DISPLAYING INTERFACE
$page_title = "Add New User"; // Set title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Add New User</h1>
<p>Fill in the information in the form below.</p>

<?php if (isset($alert_message)): ?>
    <div class="alert alert-<?= htmlspecialchars($alert_message['type']) ?>">
        <?= htmlspecialchars($alert_message['message']) ?>
    </div>
<?php endif; ?>

<div class="content-box">
    <form action="handlers/process_add_user.php" method="POST">
        <div class="form-group">
            <label for="name">User Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="role">Role</label>
            <select class="form-control" id="role" name="role">
                <option value="student">Student</option>
                <option value="admin">Administrator (Admin)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="password2">Confirm Password</label>
            <input type="password" class="form-control" id="password2" name="password2" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Save User</button>
        <a href="manage_users.php" class="btn" style="background-color: #eee;">Cancel</a>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>