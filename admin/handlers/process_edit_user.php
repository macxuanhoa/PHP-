<?php
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form
$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'student');
$password = trim($_POST['password'] ?? '');
$password2 = trim($_POST['password2'] ?? '');

// 2. Basic data validation
$errors = [];
if (empty($id) || empty($name) || empty($email) || empty($role)) {
    $errors[] = "Please fill in all required fields (Name, Email, Role).";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
    $errors[] = "Invalid email format.";
}
$email_domain = '@gmail.com';
if (substr(strtolower($email), -strlen($email_domain)) !== $email_domain && !empty($email)) {
    $errors[] = "System only accepts @gmail.com emails.";
}

// 3. Check duplicate Email (only check with OTHER users)
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $errors[] = "This email is already registered by another user.";
    }
} catch (PDOException $e) {
    $errors[] = "Database error (checking email): " . $e->getMessage();
}

// 4. Check Password (only if entered)
$update_password = false;
if (!empty($password)) {
    if ($password !== $password2) {
        $errors[] = "Password confirmation does not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    }
    $update_password = true; // Mark that password will be updated
}

// 5. Process
if (!empty($errors)) {
    // If there are errors, save notification and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => implode('<br>', $errors) // Join errors
    ];
    header("Location: ../edit_user.php?id=$id");
    exit;
} else {
    // If no errors, proceed with database update
    try {
        if ($update_password) {
            // Update WITH password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
            $params = [$name, $email, $role, $hash, $id];
        } else {
            // Update WITHOUT password
            $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
            $params = [$name, $email, $role, $id];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Set success notification and go to list page
        $_SESSION['alert_message'] = [
            'type' => 'success',
            'message' => 'User updated successfully!'
        ];
        header('Location: ../manage_users.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Database error when updating: ' . $e->getMessage()
        ];
        header("Location: ../edit_user.php?id=$id");
        exit;
    }
}
?>