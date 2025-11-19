<?php
require_once __DIR__ . '/../includes/session_manager.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/database.php';

// Kiểm tra đăng nhập
require_login();

// Lấy user info
$user = get_logged_user();
$user_id = $user['id'];

// Lấy thống kê từ database helper
try {
    $totalPosts = DatabaseHelper::getTotalUserPosts($user_id);
    $totalSubjects = DatabaseHelper::getTotalSubjects();
    $postsLast7Days = DatabaseHelper::getPostsLast7Days($user_id);
    
    // Posts per day
    $rows = DatabaseHelper::getPostsPerDayLast7Days($user_id);

    $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $counts = array_fill(0, 7, 0);
    foreach ($rows as $r) {
        $i = array_search(substr($r['day_name'], 0, 3), $weekDays);
        if ($i !== false) $counts[$i] = (int)$r['count'];
    }

    $posts_per_day_labels_json = json_encode($weekDays);
    $posts_per_day_json = json_encode($counts);
} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="vi" class="light-mode">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard — Student Portal</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Orbitron:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="../footer/footer.css">
</head>

<body class="light-mode">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="wrap">
        <section class="grid">

            <div class="card small">
                <h3><i class="fa-solid fa-file-alt"></i>Total Posts</h3>
                <div class="value" data-value="<?= $totalPosts ?>">0</div>
            </div>

            <div class="card small">
                <h3><i class="fa-solid fa-book"></i>Total Subjects</h3>
                <div class="value" data-value="<?= $totalSubjects ?>">0</div>
            </div>

            <div class="card small">
                <h3><i class="fa-solid fa-calendar-week"></i>Posts (7 days)</h3>
                <div class="value" data-value="<?= $postsLast7Days ?>">0</div>
            </div>

            <div class="card wide">
                <div>
                    <h3 style="margin-bottom:0.25rem;color:var(--text)">Recent Activity</h3>
                    <div style="color:var(--muted);font-size:0.9rem">Posts per day (last 7 days)</div>
                </div>
                <div class="chart-area"><canvas id="activityChart"></canvas></div>
            </div>

            <div class="card">
                <h3>Quick Actions</h3>

                <div style="display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap">
                    <a href="add_post.php" class="quick-action-btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        <span>New Post</span>
                    </a>
                    <a href="subjects.php" class="quick-action-btn btn-secondary">
                        <i class="fa-solid fa-book"></i>
                        <span>Subject List</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <?php include __DIR__ . '/../student/includes/taskbar.php'; ?>
    <?php include __DIR__ . '/../footer/footer.php'; ?>
    
    <script>
        const ctx = document.getElementById('activityChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(0,191,178,0.35)');
        gradient.addColorStop(1, 'rgba(26,62,111,0.02)');

        // === FIX: Separate config for updates ===
        const chartConfig = {
            type: 'line',
            data: {
                labels: <?= $posts_per_day_labels_json ?>,
                datasets: [{
                    label: 'Posts', // Add label
                    data: <?= $posts_per_day_json ?>,
                    borderColor: 'rgba(0,191,178,0.95)',
                    borderWidth: 2.6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgba(0,191,178,0.98)',
                    pointRadius: 6,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }, // Use default light mode color
                        ticks: {
                            color: '#627D98'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }, // Use default light mode color
                        ticks: {
                            color: '#627D98'
                        }
                    }
                }
            }
        };

        const activityChart = new Chart(ctx, chartConfig);

        /* === (NEW) UPGRADE EFFECT: COUNT-UP === */
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Run animation for all .value cards
        document.querySelectorAll('.card .value[data-value]').forEach(el => {
            const targetValue = parseInt(el.getAttribute('data-value'), 10);
            // Only run animation if value > 0
            if (targetValue > 0) {
                animateValue(el, 0, targetValue, 1500); // 1500ms = 1.5 seconds
            } else {
                el.innerHTML = targetValue; // If value is 0, display immediately
            }
        });

        const themeToggle = document.getElementById('themeToggle'); // Assume this button is in header.php
        const body = document.body;

        // 1. Apply saved theme when page loads
        const isDark = body.classList.contains('dark-mode');
        updateChartColors(isDark); // Update chart

        // 2. Theme toggle button (now handled by theme_manager.php)
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                // Theme toggle is now handled by the unified theme manager
                window.toggleTheme();
            });
        }

        // 3. Function to update chart colors
        function updateChartColors(isDark) {
            if (isDark) {
                // Use Dark colors (From Code 2)
                chartConfig.options.scales.x.grid.color = 'rgba(255,255,255,0.05)';
                chartConfig.options.scales.y.grid.color = 'rgba(255,255,255,0.05)';
                chartConfig.options.scales.x.ticks.color = '#9aa9b8';
                chartConfig.options.scales.y.ticks.color = '#9aa9b8';
            } else {
                // Use Light colors (Default from Code 1)
                chartConfig.options.scales.x.grid.color = 'rgba(0,0,0,0.05)';
                chartConfig.options.scales.y.grid.color = 'rgba(0,0,0,0.05)';
                chartConfig.options.scales.x.ticks.color = '#627D98';
                chartConfig.options.scales.y.ticks.color = '#627D98';
            }
            activityChart.update(); // Update chart
        }
    </script>
</body>

</html>