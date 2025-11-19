<?php
// 1. Start session
session_start();

// 2. Delete all session variables
$_SESSION = [];

// 3. Destroy session on server
session_destroy();

// 4. Delete session cookie on browser (simple, reliable)
if (ini_get("session.use_cookies")) {
    // Specify path '/' to delete site-wide cookie
    setcookie(session_name(), '', time() - 3600, '/');
}

// 5. Redirect to login page
header("Location: home/home.php");
exit;
?>
