<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// 1. Check if ID is passed
if (!isset($_GET['id'])) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Subject not found for editing.'];
    header('Location: manage_subjects.php');
    exit;
}

$subject_id = $_GET['id'];

// 2. Get current subject information from database
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    // If subject not found
    if (!$subject) {
        $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Invalid subject ID.'];
        header('Location: manage_subjects.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['alert_message'] = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    header('Location: manage_subjects.php');
    exit;
}

// START DISPLAYING INTERFACE
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Edit Subject</h1>
<p>Update information for subject: <strong><?= htmlspecialchars($subject['name']) ?></strong></p>

<div class="content-box">
    <form action="handlers/process_edit_subject.php" method="POST">
        
        <input type="hidden" name="id" value="<?= $subject['id'] ?>">
        
        <div class="form-group">
            <label for="name">Subject Name</label>
            <input type="text" class="form-control" id="name" name="name" 
                   value="<?= htmlspecialchars($subject['name']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" 
                      name="description"><?= htmlspecialchars($subject['description']) ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="manage_subjects.php" class="btn" style="background-color: #eee;">Cancel</a>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>