<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// --- 1. CHECK LOGIN ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get search query - require at least 2 characters
$search_query = trim($_GET['search'] ?? '');
$search_error = '';

if (isset($_GET['search'])) {
    if (empty($search_query)) {
        $search_error = 'Please enter search terms to search';
    } elseif (strlen($search_query) < 2) {
        $search_error = 'Please enter at least 2 characters to search';
        $search_query = '';
    }
}

// --- 2. Get user's post list (UPGRADED TO GET TAGS) ---
try {
    // ===== START CHANGE: MODIFY SQL TO GET TAGS =====
    $sql = "
        SELECT 
            p.*,
            s.name AS subject_name,
            GROUP_CONCAT(t.name SEPARATOR ', ') AS tag_list
        FROM posts p
        LEFT JOIN subjects s ON p.subject_id = s.id
        LEFT JOIN post_tags pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        WHERE p.user_id = :user_id";
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $sql .= " AND (p.title LIKE :search OR p.content LIKE :search)";
    }
    
    $sql .= "
        GROUP BY p.id
        ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $params = ['user_id' => $user_id];
    if (!empty($search_query)) {
        $params[':search'] = '%' . $search_query . '%';
    }
    
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    // ===== END OF CHANGES =====
} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}

// --- 3. Handle toast notification (Keep as is) ---
$toast_msg = '';
$toast_type = '';

if (isset($_GET['status']) && isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'Post_Deleted':
            $toast_msg = 'Post deleted successfully!';
            $toast_type = 'success';
            break;
        case 'Permission_Denied':
            $toast_msg = 'You do not have permission to delete this post.';
            $toast_type = 'error';
            break;
        case 'DB_Error':
            $toast_msg = 'An error occurred. Please try again.';
            $toast_type = 'error';
            break;
        case 'Invalid_ID':
            $toast_msg = 'Invalid post ID.';
            $toast_type = 'error';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Posts</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

<style>
/* ... (All original CSS kept as is) ... */
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

html, body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: background 0.4s, color 0.4s;
  overflow-x: hidden;
}

/* ===== MAIN CONTAINER ===== */
.main-container {
  padding: 10px 2rem 3rem;
  max-width: 1300px;
  margin: 0 auto;
  min-height: calc(100vh - 100px);
}
@media (max-width: 768px) { .main-container { padding: 80px 1.5rem 2rem; } }
@media (max-width: 480px) { .main-container { padding: 60px 1rem 1rem; } }

/* ===== PAGE TITLE ===== */
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--accent-b);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
  animation: fadeInLeft 0.6s ease-out;
}
@keyframes fadeInLeft {
  from { opacity: 0; transform: translateX(-20px); }
  to { opacity: 1; transform: translateX(0); }
}
.page-title i { color: var(--accent-a); }
body.dark-mode .page-title { color: var(--accent-a); }

/* ===== POSTS GRID ===== */
.posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem; }
@media (max-width: 768px) { .posts-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; } }
@media (max-width: 480px) { .posts-grid { grid-template-columns: 1fr; gap: 1rem; } }

/* ===== POST CARD ===== */
.post-card {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  backdrop-filter: blur(10px);
  border: 1px solid var(--border-color);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  animation: fadeInUp 0.6s ease-out both;
  opacity: 0;
}
.post-card:nth-child(1) { animation-delay: 0.1s; }
.post-card:nth-child(2) { animation-delay: 0.2s; }
.post-card:nth-child(3) { animation-delay: 0.3s; }
.post-card:nth-child(4) { animation-delay: 0.4s; }
.post-card:nth-child(5) { animation-delay: 0.5s; }
.post-card:nth-child(n+6) { animation-delay: 0.6s; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.post-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: var(--shadow-hover); border-color: var(--accent-a); }

/* ===== POST IMAGE ===== */
.post-image { width: 100%; height: 200px; object-fit: cover; background: #e0e0e0; transition: transform 0.3s ease, filter 0.3s ease; }
.post-card:hover .post-image { transform: scale(1.05); filter: brightness(1.1); }
.no-image { display: flex; justify-content: center; align-items: center; height: 200px; background: rgba(0,191,178,0.08); color: var(--muted); font-size: 2.5rem; transition: background 0.3s ease; }
.post-card:hover .no-image { background: rgba(0,191,178,0.15); }
.no-image i { transition: transform 0.3s ease; }
.post-card:hover .no-image i { transform: scale(1.1); }

/* ===== POST CONTENT ===== */
.post-content { flex: 1; padding: 1.5rem; }
.post-content h3 { font-size: 1.2rem; margin: 0; font-weight: 700; color: var(--accent-b); line-height: 1.3; }
body.dark-mode .post-content h3 { color: var(--accent-a); }
.post-content h3 a { color: inherit; text-decoration: none; transition: text-decoration 0.3s ease; }
.post-content h3 a:hover { text-decoration: underline; color: var(--accent-a); }
.post-content p { margin-top: 0.8rem; color: var(--muted); font-size: 1rem; line-height: 1.6; }

/* ===== POST ACTIONS ===== */
.post-actions { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); }
body.dark-mode .post-actions { border-color: var(--border-color); }
.post-actions a { text-decoration: none; padding: 0.7rem 1rem; border-radius: 12px; font-weight: 600; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.95rem; position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 0.3rem; }
.post-actions a::before { content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; background: rgba(255,255,255,0.3); border-radius: 50%; transform: translate(-50%, -50%); transition: width 0.6s, height 0.6s; }
.post-actions a:hover::before { width: 300px; height: 300px; }
.post-actions a:hover { transform: translateY(-2px); }
.btn-edit { background: rgba(0,191,178,0.12); color: var(--accent-b); }
.btn-edit:hover { background: var(--gradient-primary); color: white; box-shadow: var(--shadow-hover); }
.btn-delete { background: rgba(255,80,80,0.1); color: #d32f2f; }
.btn-delete:hover { background: linear-gradient(135deg, #ff6b6b, #d32f2f); color: white; box-shadow: var(--shadow-hover); }

/* ===== POST META & TAGS (NEW CSS) ===== */
.post-meta { 
  margin-top: 1rem; 
  font-size: 0.9rem; 
  color: var(--muted); 
  display: flex; 
  align-items: center; 
  gap: 0.5rem 1rem; 
  flex-wrap: wrap; 
}
.post-meta span {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.post-meta i { color: var(--accent-a); }

.visibility-badge {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
}
.visibility-badge.public {
  background: rgba(0,191,178,0.15);
  color: var(--accent-a);
}
.visibility-badge.private {
  background: rgba(108, 117, 125, 0.15);
  color: var(--muted);
}

/* CSS FOR TAGS */
.post-tags {
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.post-tags .tag {
  font-size: 0.85rem;
  background: rgba(26,62,111,0.08);
  color: var(--accent-b);
  padding: 0.3rem 0.6rem;
  border-radius: 8px;
  font-weight: 500;
  display: inline-block;
}
body.dark-mode .post-tags .tag {
  background: rgba(255,255,255,0.1);
  color: #eee;
}

/* ===== SEARCH ===== */
.search-form {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  align-items: center;
  flex-wrap: wrap;
  max-width: 500px;
}

.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.6rem 1rem;
  border: 2px solid transparent;
  border-radius: var(--radius);
  background: var(--card);
  color: var(--text);
  font-size: 0.95rem;
  transition: all 0.3s ease;
  box-shadow: var(--shadow);
}

.search-input:focus {
  outline: none;
  border-color: var(--accent-a);
  box-shadow: 0 0 0 3px rgba(0, 191, 178, 0.1);
}

.search-btn {
  padding: 0.8rem 1.5rem;
  background: var(--gradient-primary);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.search-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-hover);
}

.search-result-info {
  margin-bottom: 1rem;
  color: var(--muted);
  font-size: 0.95rem;
}

/* ===== EMPTY STATE ===== */
.empty { text-align: center; padding: 4rem 2rem; background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); backdrop-filter: blur(10px); border: 1px solid var(--border-color); animation: fadeInUp 0.8s ease-out; }
.empty i { font-size: 3.5rem; color: var(--accent-a); margin-bottom: 1rem; }
.empty p { margin: 0; font-size: 1.2rem; color: var(--muted); line-height: 1.5; }
.empty a { color: var(--accent-a); text-decoration: none; font-weight: 600; transition: text-decoration 0.3s ease; }
.empty a:hover { text-decoration: underline; }

/* ===== TOAST ===== */
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: var(--card);
  color: var(--text);
  padding: 1.2rem 1.6rem;
  border-radius: 12px;
  box-shadow: var(--shadow);
  opacity: 0;
  transform: translateY(20px) translateX(20px);
  transition: all 0.5s ease;
  z-index: 9999;
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.toast.show {
  opacity: 1;
  transform: translateY(0) translateX(0);
}
.toast.success { border-left: 4px solid var(--accent-a); }
.toast.error { border-left: 4px solid #e53935; }
@media (max-width: 600px) {
  .toast {
    bottom: 16px;
    right: 16px;
    left: 16px;
    padding: 1rem;
  }
}
</style>
</head>

<body>
<?php include __DIR__ . '/../student/includes/header.php'; ?>

<div class="main-container">
  <h2 class="page-title"><i class="fa-solid fa-folder-open"></i> My Posts</h2>

  <!-- Search Form -->
  <form action="my_posts.php" method="GET" class="search-form">
    <input 
      type="search" 
      name="search" 
      class="search-input" 
      placeholder="Search your posts by title or content..."
      value="<?= htmlspecialchars($search_query) ?>"
    >
    <button type="submit" class="search-btn">
      <i class="fa-solid fa-search"></i> Search
    </button>
  </form>

  <!-- Search Result Info -->
  <?php if (isset($search_error)): ?>
    <div class="search-result-info" style="color: #ff6b6b;">
      <?= htmlspecialchars($search_error) ?>
    </div>
  <?php elseif (!empty($search_query)): ?>
    <div class="search-result-info">
      Found <?= count($posts) ?> post(s) matching "<?= htmlspecialchars($search_query) ?>"
      <a href="my_posts.php" style="margin-left: 1rem; color: var(--accent-a);">Clear search</a>
    </div>
  <?php endif; ?>

  <?php if (empty($posts)): ?>
    <div class="empty">
      <i class="fa-solid fa-box-open"></i>
      <?php if (!empty($search_query)): ?>
        <p>No posts found matching "<?= htmlspecialchars($search_query) ?>"</p>
      <?php else: ?>
        <p>You don't have any posts yet. <a href="add_post.php">Create your first post!</a></p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="posts-grid">
      <?php foreach ($posts as $post): ?>
        <div class="post-card">
          <?php if ($post['image']): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($post['image']) ?>" class="post-image" alt="">
          <?php else: ?>
            <div class="no-image"><i class="fa-regular fa-image"></i></div>
          <?php endif; ?>

          <div class="post-content">
            <h3><a href="post_detail.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
            <p><?= nl2br(htmlspecialchars(substr($post['content'], 0, 150))) ?><?= strlen($post['content']) > 150 ? '...' : '' ?></p>
            
            <?php if (!empty($post['tag_list'])): ?>
              <div class="post-tags">
                <?php 
                // Split the tag_list string (e.g., "PHP, SQL") into array
                $tags = explode(', ', $post['tag_list']);
                foreach ($tags as $tag): 
                ?>
                  <span class="tag">#<?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            
            <div class="post-meta">
              <span><i class="fa-solid fa-book"></i> <?= htmlspecialchars($post['subject_name'] ?? 'Unknown') ?></span>
              <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
              
              <?php if ($post['visibility'] == 'public'): ?>
                <span class="visibility-badge public"><i class="fa-solid fa-globe"></i> Public</span>
              <?php else: ?>
                <span class="visibility-badge private"><i class="fa-solid fa-lock"></i> Private</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="post-actions">
            <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="delete_post.php?id=<?= $post['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this post?')">
              <i class="fa-solid fa-trash"></i> Delete
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../student/includes/taskbar.php'; ?>

<?php if($toast_msg): ?>
<div id="toast" class="toast <?= $toast_type ?>"><?= htmlspecialchars($toast_msg) ?></div>
<script>
  const toast = document.getElementById('toast');
  toast.classList.add('show');
  setTimeout(() => { toast.classList.remove('show'); }, 3000);
</script>
<?php endif; ?>

</body>
</html>