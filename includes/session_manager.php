<?php
/**
 * Session Manager - Quản lý session và authentication chung
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra user đã đăng nhập chưa
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login_register/login.php');
        exit();
    }
}

// Kiểm tra admin đã đăng nhập chưa
function require_admin_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ../admin/login.php');
        exit();
    }
}

// Lấy thông tin user hiện tại
function get_logged_user() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'avatar' => $_SESSION['avatar'] ?? ''
    ];
}

// Lấy thông tin admin hiện tại
function get_logged_admin() {
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'email' => $_SESSION['admin_email'] ?? ''
    ];
}

// Save current URL để redirect back
function save_current_url() {
    $current_url = $_SERVER['REQUEST_URI'];
    if (basename($current_url) !== 'contact.php') {
        $_SESSION['previous_url'] = $current_url;
    }
}

// Get current page name cho active menu
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}
?>
