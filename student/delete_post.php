<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/config.php';

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// --- Check valid ID ---
if (!$post_id) {
    header("Location: my_posts.php?status=error&msg=Invalid_ID");
    exit;
}

try {
    // --- Check ownership ---
    $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header("Location: my_posts.php?status=error&msg=Permission_Denied");
        exit;
    }

    // --- Delete post ---
    $deleteStmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$post_id, $user_id]);

    // --- Delete image ---
    if (!empty($post['image'])) {
        $imagePath = dirname(__DIR__) . '/assets/uploads/' . $post['image'];
        if (file_exists($imagePath)) unlink($imagePath);
    }

    // --- Redirect ---
    header("Location: my_posts.php?status=success&msg=Post_Deleted");
    exit;

} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    header("Location: my_posts.php?status=error&msg=DB_Error");
    exit;
}
?>
