<?php
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Save current URL to session so contact.php knows where to go back
save_current_url();

// Variable to highlight active button
$currentPage = get_current_page();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../student/assets/css/header.css">
    <link rel="stylesheet" href="../student/assets/css/toast.css">
    <link rel="stylesheet" href="../student/assets/css/mention.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <title>Student Portal</title>
</head>

<header class="main-header">
    <div class="header-left">
        <a href="dashboard.php" class="logo">
            <div class="logo-icon">
                <?= strtoupper(substr(htmlspecialchars($_SESSION['username'] ?? 'SP'), 0, 2)) ?>
            </div>
            <div class="logo-text">
                <span class="welcome-text">Welcome back,</span>
                <span class="user-name"><?= htmlspecialchars(explode(' ', $_SESSION['username'] ?? 'User')[0]) ?></span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="../home/home.php" class="<?= $currentPage === 'home.php' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i><span>Home</span></a>
            <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
            <a href="add_post.php" class="<?= $currentPage === 'add_post.php' ? 'active' : '' ?>"><i class="fa-solid fa-plus"></i><span>Add Post</span></a>
            <a href="my_posts.php" class="<?= $currentPage === 'my_posts.php' ? 'active' : '' ?>"><i class="fa-solid fa-folder-open"></i><span>My Posts</span></a>
            <a href="subjects.php" class="<?= $currentPage === 'subjects.php' ? 'active' : '' ?>"><i class="fa-solid fa-book"></i><span>Subjects</span></a>
            <a href="global_feed.php" class="<?= $currentPage === 'global_feed.php' ? 'active' : '' ?>"><i class="fa-solid fa-globe"></i><span>Global Feed</span></a>
                    </nav>
    </div>

    <div class="header-right">
        <div class="user-menu">
            <button class="user-btn" id="userMenuBtn" aria-label="User menu">
                <div class="user-avatar">
                    <?php
                    $avatar = !empty($_SESSION['avatar']) ? '../uploads/avatars/' . $_SESSION['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'U') . '&background=4f46e5&color=fff';
                    ?>
                    <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>" class="avatar-img">
                </div>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars(explode(' ', $_SESSION['username'] ?? 'User')[0]) ?></span>
                    <span class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                </div>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="<?= dirname($_SERVER['PHP_SELF'], 2) ?>/logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const userBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.user-menu');
    
    if (userBtn && userDropdown && userMenu) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShowing = userDropdown.classList.toggle('show');
            userMenu.classList.toggle('has-dropdown-show', isShowing);
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (userDropdown && userMenu && !e.target.closest('.user-menu')) {
            userDropdown.classList.remove('show');
            userMenu.classList.remove('has-dropdown-show');
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    if (userDropdown) {
        userDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});
</script>
<script src="../student/assets/js/toast.js"></script>
<script src="../student/assets/js/mention.js"></script>

<?php include __DIR__ . '/theme_manager.php'; ?>
