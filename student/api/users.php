<?php
session_start();
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get all users for mention system
try {
    $stmt = $pdo->query("
        SELECT id, name, email, avatar 
        FROM users 
        ORDER BY name ASC
        LIMIT 100
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add avatar URL
    foreach ($users as &$user) {
        $user['avatar'] = !empty($user['avatar']) 
            ? '../uploads/avatars/' . $user['avatar'] 
            : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=1abc9c&color=fff";
    }
    
    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
