<?php
// Get current file name, e.g., "dashboard.php"
$current_page = basename($_SERVER['SCRIPT_NAME']);

// --- Define page groups ---
$user_pages = ['manage_users.php', 'add_user.php', 'edit_user.php'];
$post_pages = ['manage_posts.php', 'add_post.php', 'edit_post.php'];
$subject_pages = ['manage_subjects.php', 'add_subject.php', 'edit_subject.php'];
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        Admin
    </div>
    
    <ul class="sidebar-nav">
        <li class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        </li>
        
        <li class="<?= (in_array($current_page, $user_pages)) ? 'active' : '' ?>">
            <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
        </li>
        
        <li class="<?= (in_array($current_page, $post_pages)) ? 'active' : '' ?>">
            <a href="manage_posts.php"><i class="fa-solid fa-file-alt"></i> Manage Posts</a>
        </li>
        
        <li class="<?= (in_array($current_page, $subject_pages)) ? 'active' : '' ?>">
            <a href="manage_subjects.php"><i class="fa-solid fa-book"></i> Manage Subjects</a>
        </li>
        
        <li class="<?= ($current_page == 'user_analytics.php') ? 'active' : '' ?>">
            <a href="user_analytics.php"><i class="fa-solid fa-chart-line"></i> User Analytics</a>
        </li>
        
        <li class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <a href="settings.php"><i class="fa-solid fa-cogs"></i> Settings</a>
        </li>
        
        <hr style="border-color: #34495e; margin: 1rem 1.5rem;">
        
        <li><a href="../home/home.php"><i class="fa-solid fa-arrow-left"></i> Back to Site</a></li>
        
        <li><a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

<main class="admin-content">