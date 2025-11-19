<?php
session_start();
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$subject_id = $_POST['subject_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null; // Get author ID from session

// 2. Validate data
$errors = [];
if (empty($title)) {
    $errors[] = "Title is required.";
}
if (empty($subject_id)) {
    $errors[] = "You must select a subject.";
}
if (empty($user_id)) {
    $errors[] = "Login session error, author not found."; // Serious error
}

// 3. Process
if (!empty($errors)) {
    // If there are errors, save notification and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => implode('<br>', $errors)
    ];
    header('Location: ../add_post.php');
    exit;
} else {
    // If no errors, proceed with database insert
    try {
        $sql = "INSERT INTO posts (title, content, user_id, subject_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $content, $user_id, $subject_id]);
        
        // Set success notification and go to list page
        $_SESSION['alert_message'] = [
            'type' => 'success',
            'message' => 'New post added successfully!'
        ];
        header('Location: ../manage_posts.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Database error when inserting: ' . $e->getMessage()
        ];
        header('Location: ../add_post.php');
        exit;
    }
}
?>