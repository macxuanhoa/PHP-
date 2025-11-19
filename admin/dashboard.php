<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// --- Get statistics data ---
$totalPosts = 0;
$totalSubjects = 0;
$totalUsers = 0;

try {
    $totalPosts = $pdo->query("SELECT COUNT(id) FROM posts")->fetchColumn();
    $totalSubjects = $pdo->query("SELECT COUNT(id) FROM subjects")->fetchColumn();
    $totalUsers = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();

    // --- (NEW) Get 7-day chart data ---
    $sql = "
        SELECT
            DATE_FORMAT(d.date, '%a') as day_abbreviation,
            COUNT(p.id) as post_count
        FROM (
            SELECT CURDATE() - INTERVAL 6 DAY as date
            UNION ALL SELECT CURDATE() - INTERVAL 5 DAY
            UNION ALL SELECT CURDATE() - INTERVAL 4 DAY
            UNION ALL SELECT CURDATE() - INTERVAL 3 DAY
            UNION ALL SELECT CURDATE() - INTERVAL 2 DAY
            UNION ALL SELECT CURDATE() - INTERVAL 1 DAY
            UNION ALL SELECT CURDATE() - INTERVAL 0 DAY
        ) d
        LEFT JOIN posts p ON DATE(p.created_at) = d.date
        GROUP BY d.date, day_abbreviation
        ORDER BY d.date ASC
    ";
    
    $stmt = $pdo->query($sql);
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $posts_per_day_labels = [];
    $posts_per_day_data = [];

    foreach ($chart_data as $row) {
        $posts_per_day_labels[] = $row['day_abbreviation'];
        $posts_per_day_data[] = $row['post_count'];
    }
    
    $posts_per_day_labels_json = json_encode($posts_per_day_labels);
    $posts_per_day_json = json_encode($posts_per_day_data);

} catch (PDOException $e) {
    $error = "Error getting statistics data: " . $e->getMessage();
}

// --- START DISPLAYING INTERFACE ---
$page_title = "Dashboard"; 
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<h1>Dashboard</h1>
<p>Welcome to the admin area.</p>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-info">
            <h3>Posts</h3>
            <p><?= $totalPosts ?></p>
        </div>
        <div class="stat-card-icon">
            <i class="fa-solid fa-file-alt"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <h3>Subjects</h3>
            <p><?= $totalSubjects ?></p>
        </div>
        <div class="stat-card-icon">
            <i class="fa-solid fa-book"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-info">
            <h3>Members</h3>
            <p><?= $totalUsers ?></p>
        </div>
        <div class="stat-card-icon">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <h3>7-Day Activity (New Posts)</h3>
    <div style="height: 300px;">
        <canvas id="activityChart"></canvas>
    </div>
</div>

<?php
// 3. Load Footer (closes </main>, </body>, </html>)
require_once __DIR__ . '/includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('activityChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $posts_per_day_labels_json ?? '[]' ?>,
                datasets: [{
                    label: 'New Posts',
                    data: <?= $posts_per_day_json ?? '[]' ?>,
                    borderColor: '#1abc9c',
                    backgroundColor: 'rgba(26, 188, 156, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>