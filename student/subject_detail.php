<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/config.php';

// --- 1. CHECK LOGIN ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- 2. GET SUBJECT INFORMATION ---
if (!isset($_GET['id'])) die("⚠️ Subject not found.");
$subject_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id=?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$subject) die("❌ Subject does not exist.");
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// --- 3. GET USER'S POST LIST ---
try {
    $stmt = $pdo->prepare("
        SELECT id, title, created_at 
        FROM posts 
        WHERE subject_id = ? AND user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$subject_id, $user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $posts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($subject['name']) ?> - Student Portal</title>
<link rel="stylesheet" href="../student/assets/css/header.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../footer/footer.css">

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
  height: 100%;
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: background 0.4s, color 0.4s;
  overflow-x: hidden;
}

/* ===== MAIN ===== */
main {
  max-width: 1200px;
  margin: 110px auto 3rem;
  padding: 0 2rem;
  animation: fadeIn 0.6s ease;
  min-height: calc(100vh - 110px);
}
@keyframes fadeIn { from { opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }

h2.section-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 2rem;
  color: var(--accent-b);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
h2.section-title i { color: var(--accent-a); }
body.dark-mode h2.section-title { color: var(--accent-a); }

/* ===== CARD POST ===== */
.card-post {
  background: var(--card);
  border-radius: var(--radius);
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
  transition: box-shadow 0.3s ease, transform 0.3s ease;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.1);
}
.card-post:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-hover);
}
.card-post .title {
  font-weight: 700;
  font-size: 1.4rem;
  color: var(--accent-b);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.card-post .title i { color: var(--accent-a); }
.card-post p { font-size: 1rem; line-height: 1.6; color: var(--text); }

/* ===== TABLE POSTS ===== */
.table-posts { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.table-posts th, .table-posts td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
.table-posts th { background: rgba(0,191,178,0.1); color: var(--accent-b); font-weight: 600; }
.table-posts td { color: var(--text); }
.table-posts a {
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  background: var(--accent-a);
  color: #fff;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.table-posts a::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(255,255,255,0.3);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}
.table-posts a:hover::before { width: 300px; height: 300px; }
.table-posts a:hover { background: var(--accent-b); transform: translateY(-2px); }

/* ===== NO DATA ===== */
.no-data {
  text-align: center;
  font-size: 1.2rem;
  color: var(--muted);
  margin-top: 2rem;
  padding: 3rem 2rem;
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.1);
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}
.no-data i {
  font-size: 2.5rem;
  color: var(--accent-a);
  margin-bottom: 1rem;
  display: block;
}
</style>
</head>
<body>

<?php include '../student/includes/header.php'; ?>
<main>
  <h2 class="section-title"><i class="fa-solid fa-book"></i> <?= htmlspecialchars($subject['name']) ?></h2>

  <div class="card-post">
    <h4 class="title"><i class="fa-solid fa-info-circle"></i> Subject Introduction</h4>
    <p>
      <?= $subject['description'] 
          ? nl2br(htmlspecialchars($subject['description'])) 
          : "Subject <strong>" . htmlspecialchars($subject['name']) . "</strong> provides knowledge and fundamental skills to help students develop professionally." ?>
    </p>
  </div>

  <div class="card-post">
    <h4 class="title"><i class="fa-solid fa-file-lines"></i> Your Posts</h4>
    <?php if (count($posts) > 0): ?>
      <table class="table-posts">
        <thead>
          <tr><th>#</th><th>Title</th><th>Created Date</th><th>Link</th></tr>
        </thead>
        <tbody>
          <?php foreach ($posts as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= date('d/m/Y, H:i', strtotime($p['created_at'])) ?></td>
            <td><a href="post_detail.php?id=<?= $p['id'] ?>"><i class="fa-solid fa-eye"></i> View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-data">
        <i class="fa-regular fa-file-lines"></i>
        You haven't created any posts for this subject yet.
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
// Theme is now handled by theme_manager.php
</script>

<?php include __DIR__ . '/../student/includes/taskbar.php'; ?>
<?php include __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
