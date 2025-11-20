<?php
require_once __DIR__ . '/../includes/session_manager.php';
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

// Render using shared admin layout (header + sidebar)
$page_title = "User Analytics";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    .analytics-container {
        background: var(--box-bg);
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: 0 8px 32px var(--shadow-color);
        margin-top: 2rem;
        border: 1px solid var(--border-color);
        backdrop-filter: blur(10px);
        animation: fadeInUp 0.6s ease;
        transition: all 0.3s ease;
    }

    .analytics-container:hover {
        box-shadow: 0 12px 48px var(--shadow-color-hover);
        transform: translateY(-2px);
    }

    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: linear-gradient(135deg, var(--primary-color), #16a085);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Sortable table headers */
    .user-stats-table th.sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
        transition: all 0.3s ease;
    }

    .user-stats-table th.sortable:hover {
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    .user-stats-table th.sortable::after {
        content: '\f0dc';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        margin-left: 0.5rem;
        opacity: 0.3;
        font-size: 0.8rem;
    }

    .user-stats-table th.sortable.asc::after {
        content: '\f0de';
        opacity: 1;
        color: var(--primary-color);
    }

    .user-stats-table th.sortable.desc::after {
        content: '\f0dd';
        opacity: 1;
        color: var(--primary-color);
    }

    .user-stats-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }

    .user-stats-table thead tr {
        background: transparent;
    }

    .user-stats-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: var(--text-dark);
        opacity: 0.7;
        border: none;
    }

    .user-stats-table tbody tr {
        background: var(--box-bg);
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid var(--border-color);
    }

    .user-stats-table tbody tr:hover {
        transform: translateY(-2px) scale(1.01);
        box-shadow: 0 8px 24px var(--shadow-color-hover);
        border-color: var(--primary-color);
    }

    .user-stats-table td {
        padding: 1.25rem 1.5rem;
        text-align: left;
        border: none;
        background: inherit;
    }

    .user-stats-table tbody tr td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }

    .user-stats-table tbody tr td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary-color), #16a085);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(26, 188, 156, 0.3);
        transition: transform 0.3s ease;
    }

    .user-avatar:hover {
        transform: rotate(360deg) scale(1.1);
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        font-weight: 700;
        margin-bottom: 0.25rem;
        font-size: 1rem;
        color: var(--text-dark);
    }

    .user-email {
        font-size: 0.85rem;
        color: #7f8c8d;
        opacity: 0.8;
    }

    .badge {
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .badge-new {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }

    .badge-recent {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
    }

    .badge-established {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }

    .rank-badge {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .rank-badge-1 {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: white;
        box-shadow: 0 4px 20px rgba(255, 215, 0, 0.4);
    }

    .rank-badge-2 {
        background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
        color: white;
        box-shadow: 0 4px 20px rgba(192, 192, 192, 0.4);
    }

    .rank-badge-3 {
        background: linear-gradient(135deg, #CD7F32, #B8722C);
        color: white;
        box-shadow: 0 4px 20px rgba(205, 127, 50, 0.4);
    }

    .rank-badge-default {
        background: var(--box-bg);
        border: 2px solid var(--border-color);
        color: var(--text-dark);
    }

    .rank-badge:hover {
        transform: scale(1.15) rotate(5deg);
    }

    .ranking-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .ranking-card {
        background: var(--box-bg);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px var(--shadow-color);
        border: 1px solid var(--border-color);
        backdrop-filter: blur(10px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        animation: fadeInUp 0.6s ease;
        position: relative;
        overflow: hidden;
    }

    .ranking-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }

    .ranking-card:hover::before {
        left: 100%;
    }

    .ranking-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 16px 48px var(--shadow-color-hover);
    }

    .ranking-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--border-color);
    }

    .ranking-title i {
        font-size: 1.5rem;
        filter: drop-shadow(0 2px 8px currentColor);
    }

    .ranking-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .ranking-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        background: rgba(0,0,0,0.02);
    }

    .ranking-item:hover {
        background: rgba(26, 188, 156, 0.1);
        transform: translateX(8px);
    }

    .ranking-number {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 0.9rem;
        margin-right: 1rem;
        transition: all 0.3s ease;
    }

    .rank-1 {
        background: linear-gradient(135deg, #FFD700, #FFA500);
        color: white;
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
    }

    .rank-2 {
        background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
        color: white;
        box-shadow: 0 4px 12px rgba(192, 192, 192, 0.4);
    }

    .rank-3 {
        background: linear-gradient(135deg, #CD7F32, #B8722C);
        color: white;
        box-shadow: 0 4px 12px rgba(205, 127, 50, 0.4);
    }

    .ranking-avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        object-fit: cover;
        background: linear-gradient(135deg, var(--primary-color), #16a085);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        margin-right: 1rem;
        box-shadow: 0 4px 12px rgba(26, 188, 156, 0.2);
        transition: transform 0.3s ease;
    }

    .ranking-avatar:hover {
        transform: rotate(8deg) scale(1.1);
    }

    .ranking-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 12px;
        object-fit: cover;
    }

    .ranking-info {
        flex: 1;
    }

    .ranking-name {
        font-weight: 700;
        margin-bottom: 0.35rem;
        font-size: 0.95rem;
        color: var(--text-dark);
    }

    .ranking-score {
        font-size: 0.85rem;
        color: var(--primary-color);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .ranking-score i {
        font-size: 0.75rem;
    }

    .search-box {
        margin-bottom: 2rem;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1.5rem 1rem 3.5rem;
        border: 2px solid var(--border-color);
        border-radius: 50px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: var(--box-bg);
        color: var(--text-dark);
        box-shadow: 0 2px 8px var(--shadow-color);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 4px 16px rgba(26, 188, 156, 0.2);
        transform: translateY(-2px);
    }

    .search-box::before {
        content: "\f002";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-color);
        font-size: 1.1rem;
    }

    .engagement-bar {
        width: 100%;
        height: 10px;
        background: rgba(0,0,0,0.05);
        border-radius: 50px;
        overflow: hidden;
        margin-top: 0.5rem;
        position: relative;
    }

    .engagement-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 50px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        position: relative;
        overflow: hidden;
    }

    .engagement-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }

    .table-responsive {
        overflow-x: auto;
        border-radius: 12px;
    }

    /* Dark mode enhancements */
    html.dark-mode .user-stats-table tbody tr {
        background: rgba(255,255,255,0.05);
    }

    html.dark-mode .user-stats-table tbody tr:hover {
        background: rgba(255,255,255,0.08);
    }

    html.dark-mode .ranking-item {
        background: rgba(255,255,255,0.02);
    }

    html.dark-mode .ranking-item:hover {
        background: rgba(26, 188, 156, 0.15);
    }

    html.dark-mode .search-input {
        background: rgba(255,255,255,0.05);
    }

    html.dark-mode .engagement-bar {
        background: rgba(255,255,255,0.1);
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-line"></i>
        User Analytics
    </h1>
    <p class="page-subtitle">Detailed user statistics with sorting and search</p>
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
                    <th class="sortable" data-sort="rank">Rank</th>
                    <th class="sortable" data-sort="name">User</th>
                    <th class="sortable" data-sort="status">Status</th>
                    <th class="sortable" data-sort="posts">Posts</th>
                    <th class="sortable" data-sort="likes_given">Likes Given</th>
                    <th class="sortable" data-sort="likes_received">Likes Received</th>
                    <th class="sortable" data-sort="comments">Comments</th>
                    <th class="sortable" data-sort="comments_received">Comments Received</th>
                    <th class="sortable" data-sort="engagement">Engagement Rate</th>
                    <th class="sortable" data-sort="joined">Joined</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <?php foreach ($userStats as $index => $user): ?>
                    <tr class="user-row" 
                        data-name="<?= strtolower($user['name']) ?>" 
                        data-email="<?= strtolower($user['email']) ?>"
                        data-rank="<?= $index + 1 ?>"
                        data-posts="<?= $user['total_posts'] ?>"
                        data-likes-given="<?= $user['total_likes_given'] ?>"
                        data-likes-received="<?= $user['total_likes_received'] ?>"
                        data-comments="<?= $user['total_comments'] ?>"
                        data-comments-received="<?= $user['total_comments_received'] ?>"
                        data-engagement="<?= $user['engagement_rate'] ?>"
                        data-status="<?= $user['user_status'] ?>"
                        data-joined="<?= strtotime($user['user_created_at']) ?>">
                        <td>
                            <div class="rank-badge rank-badge-<?= $index + 1 <= 3 ? $index + 1 : 'default' ?>">
                                <?php if ($index + 1 <= 3): ?>
                                    <i class="fas fa-crown"></i>
                                <?php else: ?>
                                    <?= $index + 1 ?>
                                <?php endif; ?>
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
                            <span class="badge badge-<?= strtolower($user['user_status']) ?>">
                                <?php if ($user['user_status'] === 'New'): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($user['user_status'] === 'Recent'): ?>
                                    <i class="fas fa-clock"></i>
                                <?php else: ?>
                                    <i class="fas fa-shield-alt"></i>
                                <?php endif; ?>
                                <?= ucfirst($user['user_status']) ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color: var(--primary-color); font-size: 1.05rem;">
                                <i class="fas fa-file-alt" style="font-size: 0.8rem; opacity: 0.7;"></i>
                                <?= number_format($user['total_posts']) ?>
                            </strong>
                        </td>
                        <td>
                            <i class="fas fa-heart" style="font-size: 0.8rem; color: #e74c3c; opacity: 0.6;"></i>
                            <?= number_format($user['total_likes_given']) ?>
                        </td>
                        <td>
                            <strong style="color: #e74c3c; font-size: 1.05rem;">
                                <i class="fas fa-heart" style="font-size: 0.8rem;"></i>
                                <?= number_format($user['total_likes_received']) ?>
                            </strong>
                        </td>
                        <td>
                            <i class="fas fa-comment" style="font-size: 0.8rem; color: #9b59b6; opacity: 0.6;"></i>
                            <?= number_format($user['total_comments']) ?>
                        </td>
                        <td>
                            <strong style="color: #9b59b6; font-size: 1.05rem;">
                                <i class="fas fa-comments" style="font-size: 0.8rem;"></i>
                                <?= number_format($user['total_comments_received']) ?>
                            </strong>
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

<?php
require_once __DIR__ . '/includes/footer.php';
?>
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

    // Sorting functionality
    let currentSort = { column: 'rank', direction: 'asc' };

    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const sortType = this.dataset.sort;
            const tbody = document.getElementById('userTableBody');
            const rows = Array.from(tbody.querySelectorAll('.user-row'));
            
            // Toggle sort direction
            if (currentSort.column === sortType) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = sortType;
                currentSort.direction = 'asc';
            }
            
            // Update header classes
            document.querySelectorAll('.sortable').forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            this.classList.add(currentSort.direction);
            
            // Sort rows
            rows.sort((a, b) => {
                let aVal, bVal;
                
                switch(sortType) {
                    case 'name':
                        aVal = a.dataset.name;
                        bVal = b.dataset.name;
                        break;
                    case 'status':
                        aVal = a.dataset.status;
                        bVal = b.dataset.status;
                        break;
                    case 'rank':
                        aVal = parseInt(a.dataset.rank);
                        bVal = parseInt(b.dataset.rank);
                        break;
                    case 'posts':
                        aVal = parseInt(a.dataset.posts);
                        bVal = parseInt(b.dataset.posts);
                        break;
                    case 'likes_given':
                        aVal = parseInt(a.dataset.likesGiven);
                        bVal = parseInt(b.dataset.likesGiven);
                        break;
                    case 'likes_received':
                        aVal = parseInt(a.dataset.likesReceived);
                        bVal = parseInt(b.dataset.likesReceived);
                        break;
                    case 'comments':
                        aVal = parseInt(a.dataset.comments);
                        bVal = parseInt(b.dataset.comments);
                        break;
                    case 'comments_received':
                        aVal = parseInt(a.dataset.commentsReceived);
                        bVal = parseInt(b.dataset.commentsReceived);
                        break;
                    case 'engagement':
                        aVal = parseFloat(a.dataset.engagement);
                        bVal = parseFloat(b.dataset.engagement);
                        break;
                    case 'joined':
                        aVal = parseInt(a.dataset.joined);
                        bVal = parseInt(b.dataset.joined);
                        break;
                    default:
                        return 0;
                }
                
                // Compare values
                if (typeof aVal === 'string') {
                    return currentSort.direction === 'asc' 
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                } else {
                    return currentSort.direction === 'asc'
                        ? aVal - bVal
                        : bVal - aVal;
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
            
            // Update rank badges after sorting
            updateRankBadges();
        });
    });

    // Update rank badges based on current order
    function updateRankBadges() {
        const visibleRows = Array.from(document.querySelectorAll('.user-row'))
            .filter(row => row.style.display !== 'none');
        
        visibleRows.forEach((row, index) => {
            const badge = row.querySelector('.rank-badge');
            const newRank = index + 1;
            
            // Update badge class
            badge.className = 'rank-badge rank-badge-' + (newRank <= 3 ? newRank : 'default');
            
            // Update badge content
            if (newRank <= 3) {
                badge.innerHTML = '<i class="fas fa-crown"></i>';
            } else {
                badge.textContent = newRank;
            }
        });
    }

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
        
        // Set initial sort indicator
        document.querySelector('.sortable[data-sort="rank"]').classList.add('asc');
    });
</script>
<?php
// Stop before rendering the old standalone layout below
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>

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
