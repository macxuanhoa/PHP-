<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/config.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Only accept POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: global_feed.php");
    exit;
}

$post_id = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$view_mode = $_POST['view'] ?? 'full'; 

// Build return link
$redirect_url = "post_detail.php?id=" . $post_id;
if ($view_mode == 'modal') {
    $redirect_url .= "&view=modal";
}
$redirect_url .= "#comments";

// Check data: if empty then go back
if (!$post_id || empty($content)) {
    header("Location: " . $redirect_url);
    exit;
}

try {
    // Add comment to database
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)");
    $stmt->execute([
        ':post_id' => $post_id,
        ':user_id' => $user_id,
        ':content' => $content
    ]);

    // Go back to post detail page
    header("Location: " . $redirect_url);
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
