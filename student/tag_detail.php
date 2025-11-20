<?php
// Include session and configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}

// Get tag ID from URL
$tag_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tag_id <= 0) {
    header("Location: global_feed.php");
    exit;
}

// Get tag information
$tag = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$tag_id]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching tag: " . $e->getMessage());
}

if (!$tag) {
    header("Location: global_feed.php");
    exit;
}

// Get posts with this tag
$posts = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as author_name, s.name as subject_name,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN subjects s ON p.subject_id = s.id
        WHERE p.status = 'published' AND p.visibility = 'public'
        AND p.tags LIKE ?
        ORDER BY p.created_at DESC
    ");
    
    $tag_pattern = '%' . $tag_id . ':' . $tag['name'] . '%';
    $stmt->execute([$tag_pattern]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching posts: " . $e->getMessage());
}

$page_title = "Posts tagged with #" . htmlspecialchars($tag['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Student Portal</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/taskbar.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fa-solid fa-hashtag"></i>
                    Posts tagged with #<?php echo htmlspecialchars($tag['name']); ?>
                </h2>
                <p class="page-description">
                    Showing all public posts with this tag
                </p>
            </div>
            
            <div class="posts-container">
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-tag"></i>
                        <h3>No posts found</h3>
                        <p>No public posts found with this tag.</p>
                        <a href="global_feed.php" class="btn-primary">
                            <i class="fa-solid fa-arrow-left"></i> Back to Forum
                        </a>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <h3 class="post-title">
                                        <a href="post_detail.php?id=<?php echo $post['id']; ?>">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="post-meta">
                                        <span class="author">
                                            <i class="fa-solid fa-user"></i>
                                            <?php echo htmlspecialchars($post['author_name'] ?? 'Anonymous'); ?>
                                        </span>
                                        <span class="date">
                                            <i class="fa-solid fa-calendar"></i>
                                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="post-content">
                                    <p><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>...</p>
                                </div>
                                
                                <?php if (!empty($post['image'])): ?>
                                    <div class="post-image-container">
                                        <img src="../assets/uploads/<?php echo htmlspecialchars($post['image']); ?>" 
                                             class="post-image" alt="Post image">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-footer">
                                    <div class="post-stats">
                                        <span class="stat">
                                            <i class="fa-solid fa-thumbs-up"></i>
                                            <?php echo $post['like_count']; ?> Likes
                                        </span>
                                        <span class="stat">
                                            <i class="fa-solid fa-comment"></i>
                                            <?php echo $post['comment_count']; ?> Comments
                                        </span>
                                    </div>
                                    
                                    <div class="post-tags">
                                        <?php
                                        if (!empty($post['tags'])):
                                            $tag_list = explode(',', $post['tags']);
                                            foreach ($tag_list as $tag_item):
                                                if (strpos($tag_item, ':') !== false):
                                                    list($tid, $tname) = explode(':', $tag_item, 2);
                                        ?>
                                            <a href="tag_detail.php?id=<?php echo $tid; ?>" class="tag">
                                                #<?php echo htmlspecialchars($tname); ?>
                                            </a>
                                        <?php
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="post-actions">
                                    <a href="post_detail.php?id=<?php echo $post['id']; ?>" class="btn-read-more">
                                        Read More <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include '../footer/footer.php'; ?>
    
    <script>
        // Theme is now handled by theme_manager.php
    </script>
</body>
</html>
