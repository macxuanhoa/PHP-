<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get messages from database
try {
    $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Count unread messages
$unread_count = 0;
foreach ($messages as $msg) {
    if ($msg['status'] === 'unread') {
        $unread_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Inbox - Contact Messages</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-b);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        body.dark-mode .page-title { color: var(--accent-a); }
        
        .stats {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .unread-badge {
            background: var(--accent-c);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .messages-table {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--hover-bg);
            font-weight: 600;
            color: var(--accent-b);
        }
        
        body.dark-mode th { color: var(--accent-a); }
        
        tr:hover {
            background: var(--hover-bg);
        }
        
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-unread {
            background: rgba(255, 107, 107, 0.15);
            color: var(--accent-c);
        }
        
        .status-read {
            background: rgba(0, 191, 178, 0.15);
            color: var(--accent-a);
        }
        
        .message-subject {
            font-weight: 600;
            color: var(--accent-b);
        }
        
        body.dark-mode .message-subject { color: var(--accent-a); }
        
        .message-preview {
            color: var(--muted);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-view {
            background: rgba(0, 191, 178, 0.12);
            color: var(--accent-b);
        }
        
        .btn-view:hover {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-delete {
            background: rgba(255, 107, 107, 0.12);
            color: var(--accent-c);
        }
        
        .btn-delete:hover {
            background: var(--gradient-secondary);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--accent-a);
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-inbox"></i> Contact Messages
            </h1>
            <div class="stats">
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?= $unread_count ?> unread</span>
                <?php endif; ?>
                <span style="color: var(--muted);"><?= count($messages) ?> total</span>
            </div>
        </div>
        
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No messages yet</h3>
                <p>When users contact you, their messages will appear here.</p>
            </div>
        <?php else: ?>
            <div class="messages-table">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td>
                                    <span class="status-badge status-<?= $message['status'] ?>">
                                        <?= ucfirst($message['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($message['name']) ?></td>
                                <td><?= htmlspecialchars($message['email']) ?></td>
                                <td class="message-subject"><?= htmlspecialchars($message['subject']) ?></td>
                                <td class="message-preview"><?= htmlspecialchars(substr($message['message'], 0, 100)) ?>...</td>
                                <td><?= date('M d, Y H:i', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="message_detail.php?id=<?= $message['id'] ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <form method="POST" action="delete_message.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                            <input type="hidden" name="id" value="<?= $message['id'] ?>">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
