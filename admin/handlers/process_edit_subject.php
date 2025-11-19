<?php
session_start();
require_once __DIR__ . '/../../includes/config.php'; // Note: path ../../

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_register/login_register.php');
    exit;
}

// 1. Get data from form (including ID)
$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

// 2. Validate data
if (empty($id) || empty($name)) {
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => 'Subject name and ID are required.'
    ];
    // If there is ID, return to edit page. If not, go to list page.
    $redirect_url = $id ? "../edit_subject.php?id=$id" : "../manage_subjects.php";
    header("Location: $redirect_url");
    exit;
}

// 3. Process UPDATE to database
try {
    $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $description, $id]);
    
    // 4. If successful, set notification and redirect to list page
    $_SESSION['alert_message'] = [
        'type' => 'success',
        'message' => 'Subject updated successfully!'
    ];
    header('Location: ../manage_subjects.php'); // Go to list page
    exit;

} catch (PDOException $e) {
    // 5. If database error (e.g., duplicate name), report error and return to form
    $_SESSION['alert_message'] = [
        'type' => 'danger',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header("Location: ../edit_subject.php?id=$id"); // Return to edit form page
    exit;
}
?>