<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_GET['id'] ?? null;
if (!$post_id || !is_numeric($post_id)) die("Invalid post ID.");

try {
    // Get all fields of the post (no longer tag_list)
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $post_id, 'user_id' => $user_id]);
    $post = $stmt->fetch();
    
    if(!$post) die("Post does not exist or you don't have permission to edit.");

    // Get current tags of the post
    $stmt = $pdo->prepare("
        SELECT t.name FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = :post_id
    ");
    $stmt->execute([':post_id' => $post_id]);
    $tags_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $post_tags_string = implode(', ', $tags_array);

} catch(PDOException $e){
    die("Query error: " . $e->getMessage());
}

$subjects = $pdo->query("SELECT id,name FROM subjects ORDER BY name ASC")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $subject_id = $_POST['subject_id'] ?? '';
    $visibility = $_POST['visibility'] ?? 'public';
    $tag_list = trim($_POST['tag_list'] ?? ''); // Tag string from input
    
    $image_name = $post['image'];
    $error = '';

    if(empty($title)||empty($content)||empty($subject_id)){
        $error = "Please fill in all required fields.";
    } else {
        // Upload image
        if(!empty($_FILES['image']['name']) && $_FILES['image']['error']===UPLOAD_ERR_OK){
            $upload_dir = dirname(__DIR__).'/assets/uploads/';
            if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            $image_name = time().'_'.basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir.$image_name);
        }
        
        if(empty($error)){
            // Update basic post
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET title=:title, content=:content, subject_id=:subject_id, image=:image, visibility=:visibility
                WHERE id=:id AND user_id=:user_id
            ");
            $stmt->execute([
                ':title'=>$title,
                ':content'=>$content,
                ':subject_id'=>$subject_id,
                ':image'=>$image_name,
                ':visibility'=>$visibility,
                ':id'=>$post_id,
                ':user_id'=>$user_id
            ]);

            // --- Handle tags ---
            $tags = array_filter(array_map('trim', explode(',', $tag_list)));

            // Delete all old tags of the post
            $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id=:post_id");
            $stmt->execute([':post_id'=>$post_id]);

            foreach($tags as $tag_name){
                if(empty($tag_name)) continue;

                // Check if tag already exists
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE name=:name");
                $stmt->execute([':name'=>$tag_name]);
                $tag = $stmt->fetch();
                if($tag){
                    $tag_id = $tag['id'];
                } else {
                    // Insert new tag
                    $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (:name)");
                    $stmt->execute([':name'=>$tag_name]);
                    $tag_id = $pdo->lastInsertId();
                }

                // Link post_tags
                $stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");
                $stmt->execute([':post_id'=>$post_id, ':tag_id'=>$tag_id]);
            }

            header("Location: my_posts.php?success=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        h1.page-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--accent-b);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .main-container {
            max-width: 1600px;
            margin: 3rem auto;
            padding: 0 1rem;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            align-items: stretch;
        }

        @media(max-width:900px) {
            .main-container {
                flex-direction: column;
            }
        }

        .form-card,
        .preview-card {
            flex: 1;
            background: var(--card);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .form-card:hover,
        .preview-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }

        .form-card label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }

        input[type="text"],
        select,
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 1rem;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }
        body.dark-mode input[type="text"],
        body.dark-mode select,
        body.dark-mode textarea,
        body.dark-mode input[type="file"] {
            background: rgba(0,0,0,0.05);
            color: #333333;
            border-color: rgba(0,0,0,0.1);
        }

        textarea {
            min-height: 180px;
            line-height: 1.6;
            resize: vertical;
        }

        button {
            background: var(--gradient-primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0.9rem 1.5rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .preview-card h2 {
            margin-top: 0;
            color: var(--accent-a);
        }

        .preview-card img {
            max-width: 100%;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .preview-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.6;
            word-break: break-word;
            flex-grow: 1;
            overflow-y: auto;
            max-height: 400px;
        }

        .form-group-item {
            margin-bottom: 1.5rem;
        }
        .form-group-item label, .form-group-item select, .form-group-item input[type="text"] {
            margin-bottom: 0.5rem; 
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../student/includes/header.php'; ?>

<h1 class="page-title"><i class="fa-solid fa-pen"></i> Edit Post</h1>

<div class="main-container">
    <div class="form-card" id="formCard">
        <?php if (!empty($error)) echo "<p style='color:red;margin-bottom:1rem;'>$error</p>"; ?>
        <form method="POST" enctype="multipart/form-data" id="editForm">
            
            <div class="form-group-item">
                <label>Title:</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>" required>
            </div>

            <div class="form-group-item">
                <label>Subject:</label>
                <select name="subject_id" id="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>" <?= $post['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-item">
                <label>Visibility:</label>
                <select name="visibility" id="visibility">
                    <option value="public" <?= ($post['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>Public (Everyone can see)</option>
                    <option value="private" <?= ($post['visibility'] ?? 'public') === 'private' ? 'selected' : '' ?>>Private (Only you can see)</option>
                </select>
            </div>

            <div class="form-group-item">
                <label>Tags (Keywords):</label>
                <input type="text" name="tag_list" id="tag_list" 
                       value="<?= htmlspecialchars($post_tags_string) ?>"
                       placeholder="e.g., PHP, CSS, Database">
            </div>
            
            <div class="form-group-item">
                <label>Content:</label>
                <textarea name="content" id="content" class="mention-enabled" required><?= htmlspecialchars($post['content']) ?></textarea>
            </div>

            <div class="form-group-item">
                <label>Illustration Image:</label>
                <input type="file" name="image" id="image" accept="image/*">
                <?php if ($post['image']): ?>
                    <p style="margin: -1rem 0 0 0;">Current image: <strong><?= htmlspecialchars($post['image']) ?></strong></p>
                <?php endif; ?>
            </div>

            <button type="submit"><i class="fa-solid fa-save"></i> Update</button>
        </form>
    </div>

    <div class="preview-card" id="previewCard">
        <h2>Post Preview</h2>

        <p><b>Mode:</b> <span id="previewVisibility" style="color:var(--accent-b)">
            <?= ($post['visibility'] ?? 'public') === 'public' ? 'Public' : 'Private' ?>
        </span></p>

        <p><b>Tags:</b> <span id="previewTags" style="color:var(--muted)">
            <?= htmlspecialchars($post_tags_string ?: 'No tags') ?>
        </span></p>

        <img id="previewImage" src="<?= $post['image'] ? '../assets/uploads/' . htmlspecialchars($post['image']) : '' ?>" alt="">

        <h3 id="previewTitle"><?= htmlspecialchars($post['title']) ?></h3>

        <p class="preview-content" id="previewContent"><?= htmlspecialchars($post['content']) ?></p>
    </div>
</div>

<script>
const titleInput = document.getElementById('title');
const contentInput = document.getElementById('content');
const imageInput = document.getElementById('image');
const visibilityInput = document.getElementById('visibility');
const tagInput = document.getElementById('tag_list');

const previewTitle = document.getElementById('previewTitle');
const previewContent = document.getElementById('previewContent');
const previewImage = document.getElementById('previewImage');
const previewVisibility = document.getElementById('previewVisibility');
const previewTags = document.getElementById('previewTags');

function setFixedHeight() {
    const formCard = document.getElementById('formCard');
    const previewCard = document.getElementById('previewCard');
    if (!formCard || !previewCard) return;
    const maxHeight = Math.max(formCard.offsetHeight, previewCard.offsetHeight);
    formCard.style.height = previewCard.style.height = maxHeight + 'px';
}

titleInput.addEventListener('input', () => previewTitle.textContent = titleInput.value);
contentInput.addEventListener('input', () => previewContent.textContent = contentInput.value);
visibilityInput.addEventListener('change', () => {
    previewVisibility.textContent = (visibilityInput.value === "public") ? "Public" : "Private";
});
tagInput.addEventListener('input', () => {
    const tagsText = tagInput.value.split(',').map(tag => tag.trim()).filter(tag => tag !== '').join(', ');
    previewTags.textContent = tagsText || 'No tags';
});
imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) previewImage.src = URL.createObjectURL(file);
});

window.addEventListener('load', setFixedHeight);
window.addEventListener('resize', setFixedHeight);

// Theme is now handled by theme_manager.php

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
