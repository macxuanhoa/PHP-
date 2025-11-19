<?php
require_once __DIR__ . '/includes/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get message ID
$message_id = $_GET['id'] ?? null;
if (!$message_id) {
    header('Location: message_list.php');
    exit;
}

// Get message from database
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        header('Location: message_list.php');
        exit;
    }
    
    // Mark as read if unread
    if ($message['status'] === 'unread') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$message_id]);
        $message['status'] = 'read';
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle reply form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $reply_content = trim($_POST['reply_message']);
    
    if ($reply_content) {
        $admin_email = 'hoamxgcd220422@fpt.edu.vn';
        $app_password = 'zwdn tmko hvbz oxjt';
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $admin_email;
            $mail->Password   = $app_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom($admin_email, 'Student Portal Admin');
            $mail->addAddress($message['email'], $message['name']);
            $mail->addReplyTo($admin_email, 'Student Portal Admin');
            
            $mail->isHTML(true);
            $mail->Subject = "Re: " . $message['subject'];
            $mail->Body    = "<p>Dear " . htmlspecialchars($message['name']) . ",</p>
                            <p>Thank you for contacting us. Here is our response to your message:</p>
                            <hr>
                            <p><strong>Your original message:</strong></p>
                            <p><em>" . htmlspecialchars($message['message']) . "</em></p>
                            <hr>
                            <p><strong>Our response:</strong></p>
                            <p>" . nl2br(htmlspecialchars($reply_content)) . "</p>
                            <hr>
                            <p>Best regards,<br>Student Portal Team</p>";
            
            $mail->send();
            
            $_SESSION['success_message'] = 'Reply sent successfully!';
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Failed to send reply: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error_message'] = 'Please enter a reply message.';
    }
    
    header('Location: message_detail.php?id=' . $message_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Details - Admin Inbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-a: #00bfb2;
            --accent-b: #1a3e6f;
            --accent-c: #ff6b6b;
            --text: #122a3a;
            --muted: #607d8b;
            --bg: #f7f9fa;
            --card: #ffffff;
            --radius: 18px;
            --shadow: 0 6px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 12px 40px rgba(0,0,0,0.15);
            --border-color: #e1e8ed;
            --hover-bg: rgba(0, 0, 0, 0.04);
            --gradient-primary: linear-gradient(135deg, var(--accent-a), var(--accent-b));
            --gradient-secondary: linear-gradient(135deg, var(--accent-b), var(--accent-c));
        }
        
        body.dark-mode {
            --bg: #0f1724;
            --text: #e8eef5;
            --muted: #9aa9b8;
            --card: rgba(255,255,255,0.08);
            --shadow: 0 8px 32px rgba(0,0,0,0.45);
            --shadow-hover: 0 16px 48px rgba(0,0,0,0.6);
            --border-color: rgba(255,255,255,0.1);
            --hover-bg: rgba(255,255,255,0.05);
        }
        
        html, body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            transition: background 0.4s, color 0.4s;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            margin-bottom: 2rem;
        }
        
        .back-link {
            color: var(--accent-a);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .message-card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .sender-info h2 {
            margin: 0 0 0.5rem 0;
            color: var(--accent-b);
            font-size: 1.5rem;
        }
        
        body.dark-mode .sender-info h2 { color: var(--accent-a); }
        
        .sender-info p {
            margin: 0.25rem 0;
            color: var(--muted);
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-read {
            background: rgba(0, 191, 178, 0.15);
            color: var(--accent-a);
        }
        
        .message-content {
            background: var(--hover-bg);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .message-subject {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--accent-b);
            margin-bottom: 1rem;
        }
        
        body.dark-mode .message-subject { color: var(--accent-a); }
        
        .message-text {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .attachment {
            background: var(--card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .attachment i {
            color: var(--accent-a);
        }
        
        .reply-section {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .reply-section h3 {
            margin-top: 0;
            color: var(--accent-b);
        }
        
        body.dark-mode .reply-section h3 { color: var(--accent-a); }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }
        
        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 0.8rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-a);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        
        .btn-secondary {
            background: var(--hover-bg);
            color: var(--text);
            margin-right: 1rem;
        }
        
        .btn-secondary:hover {
            background: var(--border-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: rgba(0, 191, 178, 0.15);
            color: var(--accent-a);
            border: 1px solid rgba(0, 191, 178, 0.3);
        }
        
        .alert-danger {
            background: rgba(255, 107, 107, 0.15);
            color: var(--accent-c);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="message_list.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Messages
            </a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="message-card">
            <div class="message-header">
                <div class="sender-info">
                    <h2><?= htmlspecialchars($message['name']) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($message['email']) ?></p>
                    <p><i class="fas fa-calendar"></i> <?= date('F d, Y H:i', strtotime($message['created_at'])) ?></p>
                </div>
                <span class="status-badge status-<?= $message['status'] ?>">
                    <?= ucfirst($message['status']) ?>
                </span>
            </div>
            
            <div class="message-content">
                <div class="message-subject"><?= htmlspecialchars($message['subject']) ?></div>
                <div class="message-text"><?= htmlspecialchars($message['message']) ?></div>
                
                <?php if ($message['attachment_name']): ?>
                    <div class="attachment">
                        <i class="fas fa-paperclip"></i>
                        <span><?= htmlspecialchars($message['attachment_name']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="reply-section">
            <h3><i class="fas fa-reply"></i> Reply to <?= htmlspecialchars($message['name']) ?></h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="reply_message">Your Reply:</label>
                    <textarea name="reply_message" id="reply_message" placeholder="Type your reply here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
                <a href="message_list.php" class="btn btn-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</body>
</html>
