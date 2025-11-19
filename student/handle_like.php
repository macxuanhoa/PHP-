<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: global_feed.php");
    exit;
}

$post_id = $_POST['post_id'] ?? null;

// ===== START CHANGE: Get return link =====
// 1. Check if 'return_to' link (from global_feed) was sent
$return_to = $_POST['return_to'] ?? null;
// 2. If not, get 'view' (from post_detail)
$view_mode = $_POST['view'] ?? 'full';

if (!$post_id) {
    // If no post_id, return to main feed page
    header("Location: global_feed.php"); 
    exit;
}

// 3. Build redirect link
if ($return_to) {
    // Case 1: Like from global_feed.php. Return to exact feed page (saved search/sort)
    // Remove empty search parameter to avoid error
    $redirect_url = preg_replace('/[&?]search=(?:&|$)/', '', $return_to);
    $redirect_url = preg_replace('/[&?]search=$/', '', $redirect_url);
    if (strpos($redirect_url, '?') === false && !empty($return_to) && strpos($return_to, '?') !== false) {
        $redirect_url = '?' . ltrim($redirect_url, '?');
    }
} else {
    // Case 2: Like from post_detail.php (in pop-up or full page)
    $redirect_url = "post_detail.php?id=" . $post_id;
    if ($view_mode == 'modal') {
        $redirect_url .= "&view=modal"; // Keep modal state
    }
}
// ===== END OF CHANGES =====

try {
    // (Like/unlike logic remains unchanged)
    $stmt_check = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id");
    $stmt_check->execute([':post_id' => $post_id, ':user_id' => $user_id]);
    $existing_like = $stmt_check->fetch();

    if ($existing_like) {
        $stmt_delete = $pdo->prepare("DELETE FROM post_likes WHERE id = :like_id");
        $stmt_delete->execute([':like_id' => $existing_like['id']]);
    } else {
        $stmt_insert = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)");
        $stmt_insert->execute([':post_id' => $post_id, ':user_id' => $user_id]);
    }

    // Redirect to prepared link
    header("Location: " . $redirect_url);
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>