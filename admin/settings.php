<?php
session_start();
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
$page_title = "Settings"; // Set title
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Account Settings</h1>
<p>Change your personal information and security settings.</p>

<?php if (isset($alert_message)): ?>
    <div class="alert alert-<?= htmlspecialchars($alert_message['type']) ?>">
        <?= htmlspecialchars($alert_message['message']) ?>
    </div>
<?php endif; ?>

<div class="content-box">
    <h2>Change Password</h2>
    <form action="handlers/process_change_password.php" method="POST">
        <div class="form-group">
            <label for="old_password">Old Password</label>
            <input type="password" class="form-control" id="old_password" name="old_password" required>
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password (at least 6 characters)</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <h2>Website Settings (Not Developed Yet)</h2>
    <p>Features like changing website name, enabling/disabling registration... can be developed here in the future.</p>
</div>


<?php
require_once __DIR__ . '/includes/footer.php';
?>