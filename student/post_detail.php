<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/config.php';

// --- 1. CHECK LOGIN AND POST ID ---
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login_register/login_register.php");
  exit;
}
$current_user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
  die("Post not found. (ID missing)");
}
$post_id = $_GET['id'];
$view_mode = $_GET['view'] ?? 'full';

// --- 2. QUERY DATA (Keep as is) ---
try {
  $sql = "
        SELECT 
            p.*, 
            u.name AS author_name, u.avatar AS author_avatar, s.name AS subject_name,
            GROUP_CONCAT(DISTINCT t.id, ':', t.name SEPARATOR ';') AS tag_data,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = :current_user_id) AS user_has_liked
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN subjects s ON p.subject_id = s.id
        LEFT JOIN post_tags pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        WHERE p.id = :post_id
        GROUP BY p.id
    ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':post_id' => $post_id, ':current_user_id' => $current_user_id]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$post) die("Post does not exist. (Invalid ID)");
  if ($post['visibility'] == 'private' && $post['user_id'] != $current_user_id) die("You don't have permission to view this post.");

  $comment_stmt = $pdo->prepare("
        SELECT c.*, u.name AS commenter_name, u.avatar AS commenter_avatar
        FROM comments c LEFT JOIN users u ON c.user_id = u.id
        WHERE c.post_id = :post_id ORDER BY c.created_at ASC
    ");
  $comment_stmt->execute([':post_id' => $post_id]);
  $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($post['title']) ?></title>

  <?php if ($view_mode != 'modal'): ?>
    <link rel="stylesheet" href="../student/assets/css/header.css">
  <?php endif; ?>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --accent-a: #00bfb2;
      --accent-b: #1a3e6f;
      --accent-c: #ff6b6b;
      --text: #122a3a;
      --muted: #607d8b;
      --bg: #f7f9fa;
      --card: #ffffff;
      --radius: 18px;
      --shadow: 0 6px 20px rgba(0,0,0,0.08);
      --shadow-hover: 0 12px 40px rgba(0,0,0,0.15);
      --border-color: #e1e8ed;
      --hover-bg: rgba(0, 0, 0, 0.04);
      --gradient-primary: linear-gradient(135deg, var(--accent-a), var(--accent-b));
      --gradient-secondary: linear-gradient(135deg, var(--accent-b), var(--accent-c));
      --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
    }

    body.dark-mode {
      --bg: #0f1724;
      --text: #e8eef5;
      --muted: #9aa9b8;
      --card: rgba(255,255,255,0.08);
      --shadow: 0 8px 32px rgba(0,0,0,0.45);
      --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
      --border-color: rgba(255,255,255,0.1);
      --hover-bg: rgba(255,255,255,0.05);
      --gradient-primary: linear-gradient(135deg, var(--accent-a), var(--accent-b));
      --gradient-secondary: linear-gradient(135deg, var(--accent-b), var(--accent-c));
      --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05));
    }

    html,
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      transition: background 0.4s, color 0.4s;
    }

    /* ===== START CHANGE (1): CLEAN CSS FOR MODAL ===== */
    main {
      max-width: 900px;
      margin: <?= ($view_mode == 'modal') ? '0' : '110px' ?> auto 3rem;
      padding: <?= ($view_mode == 'modal') ? '0' : '0 2rem' ?>;
      animation: fadeIn 0.6s ease;
    }

    .post-card {
      background: var(--card);
      border-radius: <?= ($view_mode == 'modal') ? '0' : 'var(--radius)' ?>;
      box-shadow: <?= ($view_mode == 'modal') ? 'none' : 'var(--shadow)' ?>;
      overflow: hidden;
    }

    .interaction-section {
      margin-top: <?= ($view_mode == 'modal') ? '0' : '1.5rem' ?>;
      /* Reduce padding for compactness */
      padding: <?= ($view_mode == 'modal') ? '1rem 1.5rem' : '1rem 2.5rem' ?>;
      background: var(--card);
      border-radius: <?= ($view_mode == 'modal') ? '0' : 'var(--radius)' ?>;
      box-shadow: <?= ($view_mode == 'modal') ? 'none' : 'var(--shadow)' ?>;
    }

    .post-content {
      /* Reduce padding for compactness */
      padding: <?= ($view_mode == 'modal') ? '1.5rem' : '2rem 2.5rem' ?>;
    }

    .comment-list {
      /* Reduce padding for compactness */
      padding-top: <?= ($view_mode == 'modal') ? '0.5rem' : '1rem' ?>;
      margin-top: <?= ($view_mode == 'modal') ? '1rem' : '2rem' ?>;
      border-top: 1px solid var(--border-color);
    }

    /* ===== END OF CHANGE (1) ===== */


    .post-image {
      width: 100%;
      max-height: 450px;
      object-fit: cover;
    }

    .post-author {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
    }

    .post-author img {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
    }

    .author-info {
      line-height: 1.3;
    }

    .author-name {
      font-weight: 600;
      color: var(--text);
      font-size: 1.1rem;
    }

    .post-date {
      font-size: 0.9rem;
      color: var(--muted);
    }

    .post-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--accent-b);
      margin-bottom: 1rem;
      line-height: 1.3;
    }

    body.dark-mode .post-title {
      color: var(--accent-a);
    }

    .post-full-content {
      font-size: 1.1rem;
      line-height: 1.7;
      color: var(--text);
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .post-tags {
      margin-top: 2rem;
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .post-tags a.tag {
      text-decoration: none;
      font-size: 0.85rem;
      background: rgba(26, 62, 111, 0.08);
      color: var(--accent-b);
      padding: 0.3rem 0.6rem;
      border-radius: 8px;
      font-weight: 500;
      transition: 0.3s;
    }

    .post-tags a.tag:hover {
      background: rgba(26, 62, 111, 0.2);
      transform: translateY(-2px);
    }

    body.dark-mode .post-tags a.tag {
      background: rgba(255, 255, 255, 0.1);
      color: #eee;
    }

    .post-meta {
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
      font-size: 0.9rem;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 0.5rem 1.5rem;
      flex-wrap: wrap;
    }

    body.dark-mode .post-meta {
      border-color: rgba(255, 255, 255, 0.1);
    }

    .post-meta span {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .post-meta i {
      color: var(--accent-a);
    }


    /* (CSS for .stats-bar and .actions-bar kept as is) */
    .stats-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1.5rem;
      color: var(--muted);
      font-weight: 500;
      font-size: 0.9rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 0.5rem;
    }

    .stat-link {
      color: var(--muted);
      text-decoration: none;
      transition: 0.3s;
    }

    .stat-link:hover {
      text-decoration: underline;
      color: var(--accent-b);
    }

    body.dark-mode .stat-link:hover {
      color: var(--accent-a);
    }

    .actions-bar {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
      padding: 0.5rem 0;
    }

    .action-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.8rem;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 1rem;
      background: none;
      color: var(--muted);
    }

    .action-btn:hover {
      background: rgba(0, 0, 0, 0.05);
    }

    body.dark-mode .action-btn:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .action-btn.liked {
      color: var(--accent-a);
    }

    .like-form {
      margin: 0;
      display: contents;
    }

    .comment-form {
      margin-top: 1.5rem;
    }

    .comment-form textarea {
      width: 100%;
      padding: 1rem;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 1rem;
      font-family: 'Inter', sans-serif;
      min-height: 80px;
      box-sizing: border-box;
      margin-bottom: 1rem;
    }

    .btn-submit {
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      background: var(--accent-b);
      color: #fff;
      transition: 0.3s;
    }

    .btn-submit:hover {
      background: var(--accent-a);
    }

    .comment-list h3 {
      font-size: 1.4rem;
      color: var(--accent-b);
      margin-bottom: 1.5rem;
    }

    body.dark-mode .comment-list h3 {
      color: var(--accent-a);
    }

    .comment-item {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .comment-item img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .comment-content {
      flex: 1;
      background: rgba(0, 0, 0, 0.03);
      padding: 0.8rem 1.2rem;
      border-radius: 12px;
    }

    body.dark-mode .comment-content {
      background: rgba(0, 0, 0, 0.2);
    }

    .comment-author {
      font-weight: 600;
      color: var(--text);
      font-size: 0.95rem;
    }

    .comment-text {
      font-size: 1rem;
      color: var(--text);
      line-height: 1.6;
      margin-top: 0.3rem;
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .comment-date {
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 0.3rem;
    }
  </style>
</head>

<body>

  <?php if ($view_mode != 'modal'): ?>
    <?php include '../student/includes/header.php'; ?>
  <?php endif; ?>

  <main>
    <div class="post-card">
      <?php if ($post['image']): ?>
        <img src="../assets/uploads/<?= htmlspecialchars($post['image']) ?>" class="post-image" alt="Post image">
      <?php endif; ?>
      <div class="post-content">
        <div class="post-author">
          <img src="../assets/avatars/<?= htmlspecialchars($post['author_avatar'] ?? 'default.png') ?>" alt="avatar">
          <div class="author-info">
            <div class="author-name"><?= htmlspecialchars($post['author_name'] ?? 'Anonymous') ?></div>
            <div class="post-date"><?= date('d/m/Y, H:i', strtotime($post['created_at'])) ?></div>
          </div>
        </div>
        <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="post-full-content">
          <?= nl2br(htmlspecialchars($post['content'])) ?>
        </div>
        <?php if (!empty($post['tag_data'])): ?>
          <div class="post-tags">
            <?php
            $tags = explode(';', htmlspecialchars($post['tag_data']));
            foreach ($tags as $tag_pair):
              if (strpos($tag_pair, ':') !== false):
                list($tag_id, $tag_name) = explode(':', $tag_pair, 2);
            ?>
                <a href="tag_detail.php?id=<?= $tag_id ?>" class="tag" target="_top" onclick="return false;">#<?= $tag_name ?></a>
            <?php
              endif;
            endforeach;
            ?>
          </div>
        <?php endif; ?>

        <div class="post-meta">
          <span><i class="fa-solid fa-book"></i> <?= htmlspecialchars($post['subject_name'] ?? 'Unknown') ?></span>
          <?php if ($post['visibility'] == 'public'): ?>
            <span class="visibility-badge public"><i class="fa-solid fa-globe"></i> Public</span>
          <?php else: ?>
            <span class="visibility-badge private"><i class="fa-solid fa-lock"></i> Private</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="interaction-section" id="comments">

      <div class="stats-bar">
        <a href="#" class="stat-link" onclick="toastManager.info('Feature to see who liked will be updated soon!'); return false;">
          <i class="fa-solid fa-thumbs-up"></i> <?= $post['like_count'] ?> likes
        </a>
        <a href="#comments" class="stat-link">
          <?= $post['comment_count'] ?> comments
        </a>
      </div>

      <div class="actions-bar">
        <form action="handle_like.php" method="POST" class="like-form">
          <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
          <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">

          <?php if ($post['user_has_liked'] > 0): ?>
            <button type="submit" class="action-btn liked">
              <i class="fa-solid fa-thumbs-up"></i> Liked
            </button>
          <?php else: ?>
            <button type="submit" class="action-btn">
              <i class="fa-regular fa-thumbs-up"></i> Like
            </button>
          <?php endif; ?>
        </form>

        <button class="action-btn" onclick="document.getElementById('comment-textarea').focus()">
          <i class="fa-regular fa-comment"></i> Comment
        </button>
      </div>

      <div class="comment-form">
        <form action="handle_comment.php" method="POST">
          <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
          <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
          <textarea name="content" id="comment-textarea" class="mention-enabled" placeholder="Write your comment..." required></textarea>
          <button type="submit" class="btn-submit">Send Comment</button>
        </form>
      </div>

      <div class="comment-list">
        <h3><i class="fa-solid fa-comments"></i> Comments</h3>
        <?php if (empty($comments)): ?>
          <p style="color: var(--muted); text-align: center;">No comments yet. Be the first to comment!</p>
        <?php else: ?>
          <?php foreach ($comments as $comment): ?>
            <div class="comment-item">
              <img src="../assets/avatars/<?= htmlspecialchars($comment['commenter_avatar'] ?? 'default.png') ?>" alt="avatar">
              <div class="comment-content">
                <span class="comment-author"><?= htmlspecialchars($comment['commenter_name'] ?? 'Anonymous') ?></span>
                <p class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                <span class="comment-date"><?= date('d/m/Y, H:i', strtotime($comment['created_at'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <?php if ($view_mode != 'modal'): ?>
    <?php include __DIR__ . '/../student/includes/taskbar.php'; ?>
  <?php endif; ?>

  <script>
    // Theme is now handled by theme_manager.php

    // (Tag link script unchanged)
    if (window.self !== window.top) {
      document.querySelectorAll('.post-tags a.tag').forEach(tagLink => {
        tagLink.target = '_top';
      });
    }

    // Initialize mention system
    if (typeof MentionSystem !== 'undefined') {
      const mentionSystem = new MentionSystem({
        textareaSelector: '.mention-enabled',
        apiEndpoint: 'api/users.php',
        triggerChar: '@'
      });
    }
  </script>

</body>

</html>