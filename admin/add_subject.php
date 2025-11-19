<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// START DISPLAYING INTERFACE
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Add New Subject</h1>
<p>Fill in the information in the form below.</p>

<div class="content-box">
    <form action="handlers/process_add_subject.php" method="POST">
        <div class="form-group">
            <label for="name">Subject Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Save Subject</button>
        <a href="manage_subjects.php" class="btn" style="background-color: #eee;">Cancel</a>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>