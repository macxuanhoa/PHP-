<?php
session_start();
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'student');
$password = trim($_POST['password'] ?? '');
$password2 = trim($_POST['password2'] ?? '');

// 2. Validate data
$errors = [];
if (empty($name) || empty($email) || empty($password) || empty($password2)) {
    $errors[] = "Please fill in all required fields.";
}
if ($password !== $password2) {
    $errors[] = "Password confirmation does not match.";
}
if (strlen($password) < 6 && !empty($password)) {
    $errors[] = "Password must be at least 6 characters.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
    $errors[] = "Invalid email format.";
}
// Check @gmail.com email (like register logic)
$email_domain = '@gmail.com';
if (substr(strtolower($email), -strlen($email_domain)) !== $email_domain && !empty($email)) {
    $errors[] = "System only accepts @gmail.com emails.";
}
// Check if email already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "This email is already registered.";
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// 3. Process
if (!empty($errors)) {
    // If there are errors, save notification and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => implode('<br>', $errors) // Join errors
    ];
    header('Location: ../add_user.php');
    exit;
} else {
    // If no errors, proceed with database insert
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $role]);
        
        // Set success notification and go to list page
        $_SESSION['alert_message'] = [
            'type' => 'success',
            'message' => 'New user added successfully!'
        ];
        header('Location: ../manage_users.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['alert_message'] = [
            'type' => 'danger',
            'message' => 'Database error when inserting: ' . $e->getMessage()
        ];
        header('Location: ../add_user.php');
        exit;
    }
}
?>