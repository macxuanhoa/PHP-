<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// Get user statistics with posts, likes, and comments
$userStatsQuery = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.avatar,
        u.created_at as user_created_at,
        COUNT(DISTINCT p.id) as total_posts,
        COUNT(DISTINCT pl.post_id) as total_likes_given,
        COUNT(DISTINCT pl2.post_id) as total_likes_received,
        COUNT(DISTINCT c.id) as total_comments,
        COUNT(DISTINCT c2.id) as total_comments_received,
        CASE 
            WHEN COUNT(DISTINCT p.id) > 0 THEN 
                ROUND(
                    (COUNT(DISTINCT pl2.post_id) + COUNT(DISTINCT c2.id)) * 100.0 / 
                    COUNT(DISTINCT p.id), 
                    2
                )
            ELSE 0 
        END as engagement_rate,
        CASE 
            WHEN u.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'New'
            WHEN u.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Recent'
            ELSE 'Established'
        END as user_status
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN post_likes pl ON u.id = pl.user_id
    LEFT JOIN post_likes pl2 ON p.id = pl2.post_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN comments c2 ON p.id = c.post_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY total_posts DESC, total_likes_received DESC, total_comments_received DESC
";

$userStats = $pdo->query($userStatsQuery)->fetchAll();

// Get top contributors ranking
$topPosters = $pdo->query("
    SELECT 
        u.name,
        u.avatar,
        COUNT(p.id) as post_count,
        u.id
    FROM users u
    JOIN posts p ON u.id = p.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY post_count DESC
    LIMIT 10
")->fetchAll();

$topLiked = $pdo->query("
    SELECT 
        u.name,
        u.avatar,
        COUNT(pl.post_id) as likes_received,
        u.id
    FROM users u
    JOIN posts p ON u.id = p.user_id
    JOIN post_likes pl ON p.id = pl.post_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY likes_received DESC
    LIMIT 10
")->fetchAll();

$topCommenters = $pdo->query("
    SELECT 
        u.name,
        u.avatar,
        COUNT(c.id) as comment_count,
        u.id
    FROM users u
    JOIN comments c ON u.id = c.user_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY comment_count DESC
    LIMIT 10
")->fetchAll();

// Overall statistics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM posts")->fetchColumn();
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalLikes = $pdo->query("SELECT COUNT(*) FROM post_likes")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

// Get monthly activity trends
$monthlyActivity = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as posts_count
    FROM posts
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Analytics - Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --success: #27ae60;
            --info: #16a085;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
        }

        .admin-sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 1.5rem;
            background: var(--primary);
            color: white;
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .sidebar-nav {
            list-style: none;
            padding: 1rem 0;
        }

        .sidebar-nav li {
            margin: 0.25rem 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover {
            background: rgba(52, 152, 219, 0.1);
            border-left-color: var(--primary);
        }

        .sidebar-nav a i {
            margin-right: 0.75rem;
            width: 20px;
        }

        .sidebar-nav .active a {
            background: rgba(52, 152, 219, 0.15);
            border-left-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }

        .admin-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .analytics-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-stats-table th,
        .user-stats-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .user-stats-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }

        .user-stats-table tbody tr:hover {
            background: #f8f9fa;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .user-email {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-new {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .badge-recent {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .badge-established {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
        }

        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
        }

        .rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #808080);
            color: white;
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #8B4513);
            color: white;
        }

        .rank-default {
            background: #f8f9fa;
            color: var(--dark);
        }

        .ranking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .ranking-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .ranking-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ranking-list {
            list-style: none;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .ranking-item:last-child {
            border-bottom: none;
        }

        .ranking-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            margin-right: 1rem;
        }

        .ranking-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            margin-right: 0.75rem;
        }

        .ranking-info {
            flex: 1;
        }

        .ranking-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .ranking-score {
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
        }

        .search-box {
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .engagement-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .engagement-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .ranking-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i>
                User Analytics
            </h1>
            <p class="page-subtitle">Track user contributions and engagement metrics</p>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= number_format($totalStudents) ?></div>
                <div class="stat-label">Total Students</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: var(--success);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?= number_format($activeStudents) ?></div>
                <div class="stat-label">Active Students</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?= number_format($totalPosts) ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: var(--danger);">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value"><?= number_format($totalLikes) ?></div>
                <div class="stat-label">Total Likes</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-value"><?= number_format($totalComments) ?></div>
                <div class="stat-label">Total Comments</div>
            </div>
        </div>

        <!-- Top Rankings -->
        <div class="ranking-grid">
            <div class="ranking-card">
                <h3 class="ranking-title">
                    <i class="fas fa-trophy" style="color: #FFD700;"></i>
                    Top Posters
                </h3>
                <ul class="ranking-list">
                    <?php foreach ($topPosters as $index => $user): ?>
                        <li class="ranking-item">
                            <div class="ranking-number rank-<?= $index + 1 ?>"><?= $index + 1 ?></div>
                            <div class="ranking-avatar">
                                <?= $user['avatar'] ? '<img src="' . $user['avatar'] . '" alt="">' : substr($user['name'], 0, 1) ?>
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-name"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="ranking-score"><?= number_format($user['post_count']) ?> posts</div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="ranking-card">
                <h3 class="ranking-title">
                    <i class="fas fa-heart" style="color: #e74c3c;"></i>
                    Most Liked
                </h3>
                <ul class="ranking-list">
                    <?php foreach ($topLiked as $index => $user): ?>
                        <li class="ranking-item">
                            <div class="ranking-number rank-<?= $index + 1 ?>"><?= $index + 1 ?></div>
                            <div class="ranking-avatar">
                                <?= $user['avatar'] ? '<img src="' . $user['avatar'] . '" alt="">' : substr($user['name'], 0, 1) ?>
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-name"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="ranking-score"><?= number_format($user['likes_received']) ?> likes</div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="ranking-card">
                <h3 class="ranking-title">
                    <i class="fas fa-comments" style="color: #9b59b6;"></i>
                    Top Commenters
                </h3>
                <ul class="ranking-list">
                    <?php foreach ($topCommenters as $index => $user): ?>
                        <li class="ranking-item">
                            <div class="ranking-number rank-<?= $index + 1 ?>"><?= $index + 1 ?></div>
                            <div class="ranking-avatar">
                                <?= $user['avatar'] ? '<img src="' . $user['avatar'] . '" alt="">' : substr($user['name'], 0, 1) ?>
                            </div>
                            <div class="ranking-info">
                                <div class="ranking-name"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="ranking-score"><?= number_format($user['comment_count']) ?> comments</div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Detailed User Statistics -->
        <div class="analytics-container">
            <h2 class="section-title">
                <i class="fas fa-users"></i>
                Detailed User Statistics
            </h2>

            <div class="search-box">
                <input type="text" class="search-input" id="userSearch" placeholder="Search users by name or email...">
            </div>

            <div class="table-responsive">
                <table class="user-stats-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Posts</th>
                            <th>Likes Given</th>
                            <th>Likes Received</th>
                            <th>Comments</th>
                            <th>Comments Received</th>
                            <th>Engagement Rate</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($userStats as $index => $user): ?>
                            <tr class="user-row" data-name="<?= strtolower($user['name']) ?>" data-email="<?= strtolower($user['email']) ?>">
                                <td>
                                    <div class="rank-badge rank-<?= $index + 1 <= 3 ? $index + 1 : 'default' ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= $user['avatar'] ? '<img src="' . $user['avatar'] . '" alt="">' : substr($user['name'], 0, 1) ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $user['user_status'] ?>">
                                        <?= ucfirst($user['user_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= number_format($user['total_posts']) ?></strong>
                                </td>
                                <td><?= number_format($user['total_likes_given']) ?></td>
                                <td>
                                    <strong style="color: var(--danger);"><?= number_format($user['total_likes_received']) ?></strong>
                                </td>
                                <td><?= number_format($user['total_comments']) ?></td>
                                <td>
                                    <strong style="color: var(--info);"><?= number_format($user['total_comments_received']) ?></strong>
                                </td>
                                <td>
                                    <div><?= number_format($user['engagement_rate'], 1) ?>%</div>
                                    <div class="engagement-bar">
                                        <div class="engagement-fill" style="width: <?= min($user['engagement_rate'], 100) ?>%"></div>
                                    </div>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['user_created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const name = row.dataset.name;
                const email = row.dataset.email;
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Animate engagement bars on load
        window.addEventListener('load', function() {
            const engagementBars = document.querySelectorAll('.engagement-fill');
            engagementBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>
