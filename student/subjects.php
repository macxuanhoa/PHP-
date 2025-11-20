<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/config.php';

// --- 1. CHECK LOGIN ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}

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

try {
    $sql = "
        SELECT s.id, s.name, s.created_at, COUNT(p.id) as total_posts
        FROM subjects s
        LEFT JOIN posts p ON p.subject_id = s.id";
    
    // Add search condition if search query is provided
    if (!empty($search_query)) {
        $sql .= " WHERE s.name LIKE :search";
    }
    
    $sql .= "
        GROUP BY s.id
        ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind search parameter if needed
    if (!empty($search_query)) {
        $stmt->bindValue(':search', '%' . $search_query . '%');
    }
    
    $stmt->execute();
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subject List</title>
<link rel="stylesheet" href="../student/assets/css/header.css">
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
  height: 100%;
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow-x: hidden;
}

/* ====== MAIN ====== */
main {
  max-width: 1200px;
  margin: 38px auto 3rem;
  padding: 0 2rem;
  animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ====== TITLE ====== */
.subjects-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 2rem;
  color: var(--accent-b);
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
body.dark-mode .subjects-title {
  color: var(--accent-a);
}

/* ====== GRID ====== */
.subjects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

/* ====== CARD ====== */
.subject-card {
  background: var(--card);
  border-radius: var(--radius);
  padding: 1.6rem 1.8rem;
  box-shadow: var(--shadow);
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

body.dark-mode .subject-card {
  border: 1px solid var(--border-color);
}

.subject-card::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0; height: 4px;
  background: var(--gradient-primary);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.subject-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: var(--shadow-hover);
  border-color: var(--accent-a);
}
.subject-card:hover::before {
  opacity: 1;
}

.subject-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.8rem;
  color: var(--accent-b);
}
body.dark-mode .subject-name {
  color: var(--accent-a);
}

.subject-meta {
  font-size: 0.9rem;
  color: var(--muted);
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
}

/* ====== SEARCH ====== */
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
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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

/* ====== EMPTY STATE ====== */
.no-data {
  text-align: center;
  font-size: 1.1rem;
  color: var(--muted);
  margin-top: 2rem;
  padding: 2.5rem 2rem;
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}
.no-data i {
  font-size: 2rem;
  color: var(--accent-a);
  margin-bottom: 0.8rem;
  display: block;
}
</style>
</head>
<body>

<?php include '../student/includes/header.php'; ?>

<main>
  <h2 class="subjects-title"><i class="fa-solid fa-book"></i> Subject List</h2>

  <!-- Search Form -->
  <form action="subjects.php" method="GET" class="search-form">
    <input 
      type="search" 
      name="search" 
      class="search-input" 
      placeholder="Search subjects by name..."
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
      Found <?= count($subjects) ?> subject(s) matching "<?= htmlspecialchars($search_query) ?>"
      <a href="subjects.php" style="margin-left: 1rem; color: var(--accent-a);">Clear search</a>
    </div>
  <?php endif; ?>

  <?php if (count($subjects) > 0): ?>
    <div class="subjects-grid">
      <?php foreach ($subjects as $subject): ?>
        <a href="subject_detail.php?id=<?= $subject['id'] ?>" class="subject-card">
          <div>
            <div class="subject-name"><?= htmlspecialchars($subject['name']) ?></div>
            <div class="subject-meta">
              <span><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($subject['created_at'])) ?></span>
              <span><i class="fa-solid fa-file-lines"></i> <?= $subject['total_posts'] ?> posts</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="no-data">
      <i class="fa-solid fa-layer-group"></i>
      <?php if (!empty($search_query)): ?>
        No subjects found matching "<?= htmlspecialchars($search_query) ?>".
      <?php else: ?>
        There are currently no subjects in the system.
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script>
// Theme is now handled by theme_manager.php
</script>
<?php include __DIR__ . '/../student/includes/taskbar.php'; ?>
</body>
</html>
