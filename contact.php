<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// --- Display notification (if any) ---
$alert_message = $_SESSION['alert_message'] ?? null;
unset($_SESSION['alert_message']);

// --- Determine back link ---
// If user is logged in and has previous_url => use previous_url
// If not logged in => go to home.php
$back_url = 'home/home.php'; // default is home
if (isset($_SESSION['user_id']) && isset($_SESSION['previous_url'])) {
    $back_url = $_SESSION['previous_url'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #00bfb2;
            --accent-dark: #009f96;
            --bg: #f4f7fa;
            --white: #ffffff;
            --text: #122a3a;
            --radius: 16px;
            --shadow: 0 10px 25px rgba(0,0,0,0.07);
        }
        body {
            font-family: "Inter", sans-serif;
            background: var(--bg);
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text);
        }
        .container { width: 100%; max-width: 600px; }
        .content-box {
            background: var(--white);
            padding: 2.2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            animation: fadeIn .45s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform: translateY(6px);} to { opacity:1; transform: translateY(0); } }
        h1 { text-align:center; margin-top:0; font-weight:800; color: var(--accent-dark); font-size:1.7rem; }
        .desc { text-align:center; margin-top:6px; color:#607d8b; font-size:0.95rem; }
        .form-group { margin-bottom:1.3rem; }
        label { font-weight:600; margin-bottom:6px; display:block; font-size:14px; }
        .form-control {
            width:100%;
            padding:13px 14px;
            border-radius:10px;
            border:1px solid #e1e8ed;
            font-size:15px;
            box-sizing:border-box;
            transition:0.2s;
            font-family: inherit;
        }
        .form-control:focus { border-color: var(--accent); box-shadow:0 0 0 3px rgba(0,191,178,0.15); outline:none; }
        textarea.form-control { min-height:130px; resize: vertical; }
        .btn {
            width:100%;
            padding:13px;
            font-size:15px;
            font-weight:700;
            border:none;
            border-radius:10px;
            background: var(--accent);
            color:white;
            cursor:pointer;
            transition:0.2s;
        }
        .btn:hover { background: var(--accent-dark); }
        .alert { padding:1rem; margin-bottom:1.2rem; border-radius:10px; font-weight:600; text-align:center; }
        .alert-success { background:#d4edda; color:#155724; }
        .alert-danger { background:#f8d7da; color:#721c24; }
        .back-link { display:block; text-align:center; margin-top:1.3rem; color:#607d8b; font-weight:500; text-decoration:none; }
        .back-link:hover { color: var(--accent-dark); }
    </style>
</head>
<body>
<div class="container">
    <div class="content-box">
        <h1>Contact Administrator</h1>
        <p class="desc">Send your questions or reports to the support team.</p>

        <?php if($alert_message): ?>
            <div class="alert alert-<?=htmlspecialchars($alert_message['type'])?>">
                <?=htmlspecialchars($alert_message['message'])?>
            </div>
        <?php endif; ?>

        <form action="process_contact.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Your Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label for="attachment">Attach Image (Optional)</label>
                <input type="file" id="attachment" name="attachment" class="form-control" accept="image/*">
            </div>

            <button type="submit" class="btn">Send Contact</button>
        </form>
    </div>

    <a href="<?= htmlspecialchars($back_url) ?>" class="back-link">← Back</a>
</div>
</body>
</html>
