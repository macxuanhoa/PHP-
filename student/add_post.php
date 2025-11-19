<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get list of subjects
try {
    $subjects = $pdo->query("SELECT id, name FROM subjects ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Error retrieving subject data: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $subject_id = $_POST['subject_id'] ?? '';
    $visibility = $_POST['visibility'] ?? 'public';
    $tags_string = trim($_POST['tags'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Image upload
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_name = $file_name;
        }
    }
    
    // Validate required fields
    if (empty($title) || empty($content) || empty($subject_id)) {
        header("Location: add_post.php?error=" . urlencode("Please fill in all required fields"));
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert post
        $stmt_insert_post = $pdo->prepare("
            INSERT INTO posts (title, content, subject_id, user_id, image, visibility, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt_insert_post->execute([
            $title,
            $content,
            $subject_id,
            $user_id,
            $image_name,
            $visibility
        ]);
        
        $post_id = $pdo->lastInsertId();

        // 2. Handle TAGS
        if (!empty($tags_string)) {
            $tags_array = array_filter(array_map('trim', explode(',', $tags_string)));
            
            if (!empty($tags_array)) {
                $stmt_find_tag = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt_insert_tag = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt_insert_post_tag = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");

                foreach ($tags_array as $tag_name) {
                    $stmt_find_tag->execute([$tag_name]);
                    $tag = $stmt_find_tag->fetch();
                    
                    if ($tag) {
                        $tag_id = $tag['id'];
                    } else {
                        $stmt_insert_tag->execute([$tag_name]);
                        $tag_id = $pdo->lastInsertId();
                    }
                    
                    try {
                        $stmt_insert_post_tag->execute([$post_id, $tag_id]);
                    } catch (PDOException $e_tag) {
                        // Ignore duplicate errors
                    }
                }
            }
        }
        
        $pdo->commit();
        
        header("Location: add_post.php?success=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: add_post.php?error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
}

// Rest of the code remains the same
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create New Post</title>

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

html, body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  overflow: hidden; 
}

.main-container {
  height: calc(100vh - 80px); /* Assume header height is 80px */
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
}

/* ===== NEW FORM LAYOUT ===== */
.form-card {
  background: var(--card);
  width: 90%;
  max-width: 1200px; /* Wider for 2 columns */
  height: 90vh;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.form-card h2 {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--accent-b);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 2rem 2rem 0 2rem; /* Add padding for title */
}

/* New scroll area (contains 2 columns) */
.form-layout-container {
  flex: 1; /* Auto stretch */
  overflow-y: auto;
  padding: 1rem 2rem 2rem 2rem;
}

/* 2-column grid */
.form-layout-grid {
  display: grid;
  grid-template-columns: 2fr 1fr; /* Left column 2/3, Right column 1/3 */
  gap: 2rem;
}

.form-main {
  /* Main left column */
  display: flex;
  flex-direction: column;
}

.form-sidebar {
  /* Settings right column */
  display: flex;
  flex-direction: column;
  gap: 1rem; /* Spacing between setting items */
}

/* CSS for each column (Responsive) */
@media (max-width: 900px) {
  .form-layout-grid {
    grid-template-columns: 1fr; /* Stack 1 column on mobile */
  }
}

/* ===== FORM ELEMENTS (KEEP ORIGINAL) ===== */
label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.4rem;
}
input[type="text"], select, textarea, input[type="file"] {
  width: 100%;
  padding: 1rem;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  font-size: 1rem;
  margin-bottom: 1.3rem;
  box-sizing: border-box;
  background: var(--card);
  color: var(--text);
}
body.dark-mode input[type="text"], 
body.dark-mode select, 
body.dark-mode textarea, 
body.dark-mode input[type="file"] {
  background: var(--card);
  color: var(--text);
  border-color: var(--border-color);
}

textarea {
  min-height: 250px; /* Increase height for main column */
  flex: 1; /* Auto-stretch with column */
  resize: vertical;
  line-height: 1.5;
}

/* CSS for Sidebar Column (Settings) */
.settings-box {
  background: rgba(0,0,0,0.03); /* Slightly different background */
  padding: 1rem;
  border-radius: 12px;
}
body.dark-mode .settings-box {
  background: rgba(0,0,0,0.05);
}

/* ===== BUTTONS (OUTSIDE SCROLL AREA) ===== */
.form-actions {
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  gap: 1rem;
  background: var(--card); /* Ensure buttons have background */
}
body.dark-mode .form-actions {
  border-top: 1px solid var(--border-color);
}

.btn {
  padding: 0.9rem 1.5rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: 0.3s;
}
.btn-primary {
  background: var(--gradient-primary);
  color: #fff;
}
.btn-cancel {
  background: transparent;
  border: 2px solid var(--muted);
  color: var(--muted);
}
.btn:hover { transform: translateY(-2px); }

/* ===== TOAST (KEEP ORIGINAL) ===== */
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
  transform: translateY(20px);
  transition: all 0.5s ease;
  z-index: 9999;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.success { border-left: 4px solid var(--accent-a); }
.toast.error { border-left: 4px solid #e53935; }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="main-container">
  <div class="form-card">
    <h2><i class="fa-solid fa-pen-nib"></i> Create New Post</h2>

    <form method="POST" enctype="multipart/form-data" style="display: contents;">
      
      <div class="form-layout-container">
        <div class="form-layout-grid">
          
          <div class="form-main">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" placeholder="Enter title..." required>

            <label for="content">Content:</label>
            <textarea id="content" name="content" class="mention-enabled" placeholder="Enter content..." required></textarea>

            <label for="image" style="margin-top: 1.3rem;">Illustration Image (optional):</label>
            <input type="file" id="image" name="image" accept="image/*">
          </div>
          
          <div class="form-sidebar">
            <div class="settings-box">
              <label for="subject_id">Subject:</label>
              <select id="subject_id" name="subject_id" required>
                <option value="">-- Select Subject --</option>
                <?php foreach ($subjects as $subject): ?>
                  <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="settings-box">
              <label for="visibility">Visibility Mode:</label>
              <select id="visibility" name="visibility" required>
                <option value="">-- Select Visibility --</option>
                <option value="public">Public (Everyone can see)</option>
                <option value="private">Private (Only me)</option>
              </select>
            </div>
            
            <div class="settings-box">
              <label for="tags">Tags (comma separated):</label>
              <input type="text" id="tags" name="tags" placeholder="e.g., php, sql, javascript">
            </div>
          </div>
          
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Post</button>
        <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
      </div>
      
    </form>
    </div>
</div>

<?php include __DIR__ . '/includes/taskbar.php'; ?>
<div id="toast" class="toast"></div>

<script>
// Replace old alert with toast notifications
const originalAlert = window.alert;
window.alert = function(msg) {
  if (typeof toastManager !== 'undefined') {
    toastManager.info(msg);
  } else {
    originalAlert(msg);
  }
};

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('success')) {
  if (typeof toastManager !== 'undefined') {
    toastManager.success('🎉 Post created successfully!');
  }
}
else if (urlParams.has('error')) {
  if (typeof toastManager !== 'undefined') {
    toastManager.error(decodeURIComponent(urlParams.get('error')));
  }
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