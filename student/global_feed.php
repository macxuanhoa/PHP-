<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// --- 1. Check login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];

// --- 2. Get Search & Sort Parameters ---
$search_query = trim($_GET['search'] ?? '');
$search_error = '';
$sort_by = $_GET['sort'] ?? 'newest';
$subject_filter = $_GET['subject'] ?? '';

if (isset($_GET['search'])) {
    if (empty($search_query)) {
        $search_error = 'Please enter search terms to search';
    } elseif (strlen($search_query) < 2) {
        $search_error = 'Please enter at least 2 characters to search';
        $search_query = '';
    }
} 

// --- 3. Dynamic SQL Update (Keep as is) ---
try {
    $params = [':current_user_id' => $current_user_id];
    
    $sql = "
        SELECT 
            p.*, 
            s.name AS subject_name,
            u.name AS author_name,
            u.avatar AS author_avatar,
            GROUP_CONCAT(DISTINCT t.id, ':', t.name SEPARATOR ';') AS tag_data,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = :current_user_id) AS user_has_liked
        FROM posts p 
        LEFT JOIN subjects s ON p.subject_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN post_tags pt ON p.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        WHERE p.visibility = 'public'
    ";

    if (!empty($search_query)) {
        $sql .= " AND (p.title LIKE :search OR p.content LIKE :search OR u.name LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    if (!empty($subject_filter)) {
        $sql .= " AND p.subject_id = :subject_id";
        $params[':subject_id'] = $subject_filter;
    }

    $sql .= " GROUP BY p.id";

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); 
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}

// --- 4. Get subjects for filter dropdown ---
try {
    $stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name ASC");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching subjects: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forum</title>
<link rel="stylesheet" href="../student/assets/css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

<style>
/* (All CSS kept as is) */
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
}
.feed-container {
  max-width: 800px;
  margin: 30px auto 3rem;
  padding: 0 1rem;
  animation: fadeIn 0.6s ease-out;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--accent-b);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
body.dark-mode .page-title { color: var(--accent-a); }
.filter-bar {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 2rem;
    background: var(--card);
    padding: 1.25rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    align-items: center;
    flex-wrap: wrap;
}
.search-form {
    flex-grow: 1;
    display: flex;
}
.search-input {
    flex-grow: 1;
    padding: 0.8rem 1rem;
    font-size: 1rem;
    border-radius: 8px 0 0 8px;
    border: 1px solid var(--border-color);
    border-right: none;
    background: var(--bg);
    color: var(--text);
}
.search-btn {
    padding: 0 1.2rem;
    border: none;
    background: var(--accent-a);
    color: white;
    font-size: 1.1rem;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
}
.sort-select {
    padding: 0.8rem 1rem;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--card);
    color: var(--text);
    font-weight: 500;
    transition: all 0.3s ease;
}
.sort-select:hover {
    border-color: var(--accent-a);
}
.sort-select:focus {
    outline: none;
    border-color: var(--accent-a);
    box-shadow: 0 0 0 3px rgba(0, 191, 178, 0.1);
}
.subject-select {
    padding: 0.8rem 1rem;
    font-size: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background: var(--card);
    color: var(--text);
    min-width: 180px;
    font-weight: 500;
    transition: all 0.3s ease;
}
.subject-select:hover {
    border-color: var(--accent-a);
}
.subject-select:focus {
    outline: none;
    border-color: var(--accent-a);
    box-shadow: 0 0 0 3px rgba(0, 191, 178, 0.1);
}
body.dark-mode .sort-select, body.dark-mode .search-input, body.dark-mode .subject-select {
    background: var(--bg);
    color: var(--text);
    border-color: var(--border-color);
}
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: none; 
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-overlay.active {
    display: flex; 
    animation: fadeIn 0.3s ease;
}
.modal-content {
    background: var(--card);
    width: 90%;
    max-width: 900px; 
    height: 90vh;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--accent-b);
}
body.dark-mode .modal-title { color: var(--accent-a); }
.modal-close-btn {
    font-size: 1.5rem;
    color: var(--muted);
    cursor: pointer;
    transition: 0.3s;
}
.modal-close-btn:hover {
    color: var(--accent-c);
}
.modal-body {
    flex-grow: 1;
    overflow-y: auto; 
    -webkit-overflow-scrolling: touch;
}
.modal-iframe {
    width: 100%;
    height: 100%;
    border: none;
}
.post-card {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 1.5rem;
  overflow: hidden;
}
.post-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
}
.post-header img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}
.avatar-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.1rem;
}
.author-info { line-height: 1.3; }
.author-name { font-weight: 600; color: var(--text); font-size: 1rem; }
.post-date { font-size: 0.85rem; color: var(--muted); }
.post-body {
  padding: 0 1.5rem 1rem;
}
.post-body h3 {
  font-size: 1.3rem;
  margin: 0 0 0.5rem 0;
  font-weight: 700;
  color: var(--accent-b);
  line-height: 1.3;
}
body.dark-mode .post-body h3 { color: var(--accent-a); }
.post-body h3 a { color: inherit; text-decoration: none; }
.post-body h3 a:hover { text-decoration: underline; }
.post-text {
  font-size: 1rem;
  line-height: 1.6;
  color: var(--text);
  margin-bottom: 1rem;
  word-wrap: break-word;
}
.see-more-link {
    font-weight: 600;
    color: var(--muted);
    text-decoration: none;
}
.see-more-link:hover {
    text-decoration: underline;
}
.post-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.post-tags a.tag {
  text-decoration: none;
  font-size: 0.85rem;
  background: rgba(26,62,111,0.08);
  color: var(--accent-b);
  padding: 0.3rem 0.6rem;
  border-radius: 8px;
  font-weight: 500;
  transition: 0.3s;
}
.post-tags a.tag:hover {
    background: rgba(26,62,111,0.2);
    transform: translateY(-2px);
}
body.dark-mode .post-tags a.tag {
  background: rgba(255,255,255,0.1);
  color: #eee;
}
.post-image-container {
    display: block;
    width: 100%;
    max-height: 500px;
    overflow: hidden;
    margin-top: 1rem;
    background: var(--bg);
}
.post-image {
  width: 100%;
  height: auto;
  display: block;
}
.post-footer {
  padding: 0.5rem 1.5rem 1rem;
}
.post-stats {
  font-size: 0.9rem;
  color: var(--muted);
  padding: 0.5rem 0;
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid var(--border-color);
}
.post-stats a {
    color: var(--muted);
    text-decoration: none;
    transition: 0.3s;
}
.post-stats a:hover {
    text-decoration: underline;
    color: var(--accent-b);
}
body.dark-mode .post-stats a:hover {
    color: var(--accent-a);
}
.post-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  padding-top: 0.5rem;
}
.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem;
  border-radius: 8px;
  font-weight: 600;
  color: var(--muted);
  text-decoration: none;
  transition: background 0.3s, color 0.3s;
  border: none;
  font-size: 1rem;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  background: none; 
}
.action-btn:hover {
  background: rgba(0,0,0,0.05);
}
body.dark-mode .action-btn:hover {
    background: rgba(255,255,255,0.1);
}
.action-btn.liked {
    color: var(--accent-a);
    font-weight: 700;
}
/* ===== START CHANGE (1): CSS FOR LIKE FORM ===== */
.like-form {
    margin: 0;
    display: contents; /* Make form insert into grid layout */
}
/* ===== END OF CHANGE (1) ===== */
/* Responsive Design */
@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .search-form {
        order: -1;
    }
    
    .subject-select, .sort-select {
        width: 100%;
        min-width: auto;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .feed-container {
        padding: 0 0.5rem;
    }
    
    .filter-bar {
        padding: 1rem;
    }
}

.empty { text-align: center; padding: 4rem 2rem; background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); }
.empty i { font-size: 3.5rem; color: var(--accent-a); margin-bottom: 1rem; }
.empty p { margin: 0; font-size: 1.2rem; color: var(--muted); line-height: 1.5; }
.empty a { color: var(--accent-a); text-decoration: none; font-weight: 600; }
.empty a:hover { text-decoration: underline; }
</style>
</head>

<body>
<?php include __DIR__ . '/../student/includes/header.php'; ?>

<div class="feed-container" id="feedContainer">
  <h2 class="page-title"><i class="fa-solid fa-globe"></i> Global Feed</h2>

  <?php if (!empty($search_error)): ?>
      <div class="search-error" style="color: #ff6b6b; margin-bottom: 1rem; padding: 0.8rem; background: rgba(255, 107, 107, 0.1); border-radius: 8px; border-left: 4px solid #ff6b6b;">
          <i class="fa-solid fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
          <?= htmlspecialchars($search_error) ?>
      </div>
  <?php endif; ?>

  <div class="filter-bar">
    <form action="global_feed.php" method="GET" class="search-form">
        <input 
            type="search" 
            name="search" 
            class="search-input" 
            placeholder="Search posts, authors..."
            value="<?= htmlspecialchars($search_query) ?>"
        >
        <?php if ($sort_by != 'newest'): ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
        <?php endif; ?>
        <?php if (!empty($subject_filter)): ?>
            <input type="hidden" name="subject" value="<?= htmlspecialchars($subject_filter) ?>">
        <?php endif; ?>
        <button type="submit" class="search-btn"><i class="fa-solid fa-search"></i></button>
    </form>
    
    <form action="global_feed.php" method="GET" onchange="this.submit()">
        <?php if (!empty($search_query)): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <?php endif; ?>
        <?php if ($sort_by != 'newest'): ?>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
        <?php endif; ?>
        <select name="subject" class="subject-select">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?= $subject['id'] ?>" <?= ($subject_filter == $subject['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($subject['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <form action="global_feed.php" method="GET" onchange="this.submit()">
        <?php if (!empty($search_query)): ?>
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
        <?php endif; ?>
        <?php if (!empty($subject_filter)): ?>
            <input type="hidden" name="subject" value="<?= htmlspecialchars($subject_filter) ?>">
        <?php endif; ?>
        <select name="sort" class="sort-select">
            <option value="newest" <?= ($sort_by == 'newest') ? 'selected' : '' ?>>
                Newest
            </option>
            <option value="popular" <?= ($sort_by == 'popular') ? 'selected' : '' ?>>
                Most Popular
            </option>
        </select>
    </form>
  </div>

  <?php if (empty($posts)): ?>
    <div class="empty">
      <i class="fa-solid fa-box-open"></i>
      <?php if (!empty($search_query)): ?>
        <p>No posts found matching "<?= htmlspecialchars($search_query) ?>".</p>
      <?php elseif (!empty($subject_filter)): ?>
        <?php 
        $subject_name = 'Selected Subject';
        foreach ($subjects as $subject) {
            if ($subject['id'] == $subject_filter) {
                $subject_name = htmlspecialchars($subject['name']);
                break;
            }
        }
        ?>
        <p>No posts found in <?= $subject_name ?>.</p>
      <?php else: ?>
        <p>No public posts yet. <a href="add_post.php">Create your first post!</a></p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php foreach ($posts as $post): ?>
      <div class="post-card">
        <div class="post-header">
          <?php if (!empty($post['author_avatar']) && file_exists(__DIR__ . '/../uploads/avatars/' . $post['author_avatar'])): ?>
            <img src="../uploads/avatars/<?= htmlspecialchars($post['author_avatar']) ?>?<?= time() ?>" alt="avatar">
          <?php else: ?>
            <div class="avatar-placeholder"><?= strtoupper(substr($post['author_name'] ?? 'A', 0, 1)) ?></div>
          <?php endif; ?>
          <div class="author-info">
            <div class="author-name"><?= htmlspecialchars($post['author_name'] ?? 'Anonymous') ?></div>
            <div class="post-date"><?= date('d/m/Y, H:i', strtotime($post['created_at'])) ?></div>
          </div>
        </div>
        <div class="post-body">
          <h3>
            <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal" class="open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">
              <?= htmlspecialchars($post['title']) ?>
            </a>
          </h3>
          <div class="post-text">
            <?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>
            <?php if (strlen($post['content']) > 300): ?>
                ... <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal" class="see-more-link open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">See more</a>
            <?php endif; ?>
          </div>
          <?php if (!empty($post['tag_data'])): ?>
            <div class="post-tags">
              <?php 
              $tags = explode(';', htmlspecialchars($post['tag_data']));
              foreach ($tags as $tag_pair):
                  if (strpos($tag_pair, ':') !== false):
                      list($tag_id, $tag_name) = explode(':', $tag_pair, 2);
              ?>
                <a href="tag_detail.php?id=<?= $tag_id ?>" class="tag">#<?= $tag_name ?></a>
              <?php 
                  endif;
              endforeach; 
              ?>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($post['image']): ?>
          <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal" class="post-image-container open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">
            <img src="../assets/uploads/<?= htmlspecialchars($post['image']) ?>" class="post-image" alt="Post image">
          </a>
        <?php endif; ?>
        
        <div class="post-footer">
          <div class="post-stats">
            <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal" class="stat-link open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">
                <?= $post['like_count'] ?> Likes
            </a>
            <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal#comments" class="stat-link open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">
                <?= $post['comment_count'] ?> Comments
            </a>
          </div>
          <div class="post-actions">
            
            <form action="handle_like.php" method="POST" class="like-form">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="return_to" value="global_feed.php?search=<?= htmlspecialchars($search_query) ?>&sort=<?= htmlspecialchars($sort_by) ?>&subject=<?= htmlspecialchars($subject_filter) ?>">
                
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
            
            <a href="post_detail.php?id=<?= $post['id'] ?>&view=modal#comments" class="action-btn open-modal-link" data-title="<?= htmlspecialchars($post['title']) ?>">
              <i class="fa-regular fa-comment"></i> Comment
            </a>
          </div>
        </div>
        </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal-overlay" id="postModalOverlay">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-title" id="modalTitle">Post Details</span>
            <i class="fa-solid fa-times modal-close-btn" id="modalCloseBtn"></i>
        </div>
        <div class="modal-body">
            <iframe src="about:blank" class="modal-iframe" id="postModalFrame" name="postDetailFrame"></iframe>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../student/includes/taskbar.php'; ?>

<script>
// (JavaScript for Modal with bug fixes)
document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('postModalOverlay');
    const modalFrame = document.getElementById('postModalFrame');
    const modalTitle = document.getElementById('modalTitle');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const feedContainer = document.getElementById('feedContainer');

    if (feedContainer) {
        feedContainer.addEventListener('click', function(event) {
            // Only find modal opening links
            const link = event.target.closest('a.open-modal-link'); 

            if (link) {
                event.preventDefault(); 
                
                const url = link.getAttribute('href'); 
                const title = link.getAttribute('data-title');
                
                modalFrame.setAttribute('src', url); 
                modalTitle.textContent = title;
                
                modalOverlay.classList.add('active');
                document.body.style.overflow = 'hidden'; 
            }
        });
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
        modalFrame.setAttribute('src', 'about:blank'); 
        document.body.style.overflow = 'auto'; 
        
        // RELOAD FEED PAGE WHEN MODAL CLOSES
        window.location.reload(); 
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) {
                closeModal();
            }
        });
    }
});
</script>

</body>
</html>