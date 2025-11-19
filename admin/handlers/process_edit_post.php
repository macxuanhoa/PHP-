<?php
session_start();
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form
$id = $_POST['id'] ?? null;
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$subject_id = $_POST['subject_id'] ?? null;
// $user_id (author) usually doesn't change when editing

// 2. Validate data
$errors = [];
if (empty($id)) {
    $errors[] = "Post ID is missing.";
}
if (empty($title)) {
    $errors[] = "Title is required.";
}
if (empty($subject_id)) {
    $errors[] = "You must select a subject.";
}

// 3. Process
if (!empty($errors)) {
    // If there are errors, save notification and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => implode('<br>', $errors)
    ];
    // If there is ID, return to edit page. If not, go to list page.
    $redirect_url = $id ? "../edit_post.php?id=$id" : "../manage_posts.php";
    header("Location: $redirect_url");
    exit;
} else {
    // If no errors, proceed with database update
    try {
        $sql = "UPDATE posts SET title = ?, content = ?, subject_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $content, $subject_id, $id]);
        
        // Set success notification and go to list page
        $_SESSION['alert_message'] = [
            'type' => 'success',
            'message' => 'Post updated successfully!'
        ];
        header('Location: ../manage_posts.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Database error when updating: ' . $e->getMessage()
        ];
        header("Location: ../edit_post.php?id=$id");
        exit;
    }
}
?>