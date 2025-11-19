<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get message ID
$message_id = $_POST['id'] ?? null;
if (!$message_id) {
    header('Location: message_list.php');
    exit;
}

// Delete message from database
try {
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    
    $_SESSION['success_message'] = 'Message deleted successfully!';
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Failed to delete message: " . $e->getMessage();
}

header('Location: message_list.php');
exit;
?>
