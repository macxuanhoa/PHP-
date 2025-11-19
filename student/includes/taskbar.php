<?php
require_once __DIR__ . '/../../includes/session_manager.php';
$currentPage = get_current_page();
?>

<link rel="stylesheet" href="../student/assets/css/taskbar.css"> <div class="taskbar">

  <button class="<?= $currentPage === '../contact.php' ? 'active' : '' ?>" onclick="window.location='../contact.php'">
    <i class="fa-solid fa-envelope"></i>
    <span>Contact</span>
  </button>

  <button class="icon-btn theme-toggle-btn" id="themeBtn" title="Switch theme">
    <i class="fa-solid fa-moon"></i>
    <i class="fa-solid fa-sun"></i>
  </button>

  <button onclick="if(confirm('Are you sure you want to logout?')) window.location='../logout.php';">
    <i class="fa-solid fa-power-off" style="color:#ff4d4d"></i>
    <span>Logout</span>
  </button>

</div>

<?php include __DIR__ . '/theme_manager.php'; ?>
