<?php
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

// 1. Check if user is logged in and is an admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 2. Get data from form
$user_id = $_SESSION['user_id'];
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 3. Validate data
$errors = [];
if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    $errors[] = "Please fill in all fields.";
}
if ($new_password !== $confirm_password) {
    $errors[] = "New password and confirm password do not match.";
}
if (strlen($new_password) < 6 && !empty($new_password)) {
    $errors[] = "New password must be at least 6 characters.";
}

// 4. Check old password
if (empty($errors)) {
    try {
        // Get hashed password from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Compare old password entered by user with hashed password
        if ($user && password_verify($old_password, $user['password'])) {
            // If old password is CORRECT -> Proceed with update
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hash, $user_id]);

            $_SESSION['alert_message'] = [
                'type' => 'success',
                'message' => 'Password changed successfully!'
            ];
            header('Location: ../settings.php');
            exit;
            
        } else {
            // If old password is INCORRECT
            $errors[] = "Old password is incorrect.";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// 5. If any errors, return to settings page and report errors
$_SESSION['alert_message'] = [
    'type' => 'danger',
    'message' => implode('<br>', $errors)
];
header('Location: ../settings.php');
exit;
?>