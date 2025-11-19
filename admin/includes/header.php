<?php
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    
    <title><?= $page_title ?? 'Admin Dashboard' ?> - Student Portal</title>

    <script>
        // Get saved theme from localStorage
        const savedTheme = localStorage.getItem('adminTheme');
        
        if (savedTheme === 'dark') {
            // If dark, add 'dark-mode' class to <html>
            document.documentElement.classList.add('dark-mode');
        } else {
            // Default is light
            document.documentElement.classList.remove('dark-mode');
        }
    </script>
</head>
<body>

    <header class="admin-header">
        <div class="search-bar">
            <h3>Admin Panel</h3>
        </div>
        
        <div class="header-controls">
            
            <button id="theme-toggle" class="btn-icon">
                <i class="fa-solid fa-sun icon-sun"></i> <i class="fa-solid fa-moon icon-moon"></i> </button>

            <div>
                Hello, <strong><?= htmlspecialchars($username) ?></strong>
            </div>
        </div>
    </header>
