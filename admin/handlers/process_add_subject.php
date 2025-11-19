<?php
session_start();
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

// 2. Validate data (simple example)
if (empty($name)) {
    // If error, save notification and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => 'Subject name is required.'
    ];
    header('Location: ../add_subject.php'); // Return to form page
    exit;
}

// 3. Process insert into database
try {
    $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    
    // 4. If successful, set notification and redirect to list page
    $_SESSION['alert_message'] = [
        'type' => 'success',
        'message' => 'Subject added successfully!'
    ];
    header('Location: ../manage_subjects.php'); // Go to list page
    exit;

} catch (PDOException $e) {
    // 5. If database error (e.g., duplicate name), report error and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header('Location: ../add_subject.php'); // Return to form page
    exit;
}
?>