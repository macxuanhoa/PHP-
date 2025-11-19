<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

$name       = trim($_POST['name'] ?? '');
$user_email = trim($_POST['email'] ?? '');
$subject    = trim($_POST['subject'] ?? '');
$message    = trim($_POST['message'] ?? '');

$errors = [];
if (!$name || !$user_email || !$subject || !$message) $errors[] = "Please fill in all fields.";
if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";

// Check attached file
$attachment_path = null;
$attachment_name = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file_tmp  = $_FILES['attachment']['tmp_name'];
    $file_name = $_FILES['attachment']['name'];
    $file_size = $_FILES['attachment']['size'];

    if ($file_size > 5*1024*1024) { // 5MB
        $errors[] = "File too large, maximum 5MB.";
    } else {
        $attachment_path = $file_tmp;
        $attachment_name = $file_name;
    }
}

if ($errors) {
    $_SESSION['alert_message'] = ['type'=>'danger','message'=>implode('<br>',$errors)];
    header('Location: contact.php');
    exit;
}

// Save message to database first
require_once __DIR__ . '/includes/config.php';
try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, attachment_path, attachment_name) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $user_email, $subject, $message, $attachment_path, $attachment_name]);
    $message_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    $_SESSION['alert_message'] = ['type'=>'danger','message'=>"Database error: " . $e->getMessage()];
    header('Location: contact.php');
    exit;
}

// Configure Gmail
$admin_email  = 'hoamxgcd220422@fpt.edu.vn';
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

    $mail->setFrom($admin_email, 'Student Portal');
    $mail->addReplyTo($user_email, $name);
    $mail->addAddress($admin_email, 'Admin');

    if ($attachment_path) {
        $mail->addAttachment($attachment_path, $attachment_name);
    }

    $mail->isHTML(true);
    $mail->Subject = "Student Portal: $subject";
    $mail->Body    = "<b>Name:</b> $name<br>
                      <b>Email:</b> $user_email<br><br>
                      <b>Content:</b><br>$message";

    $mail->send();

    $_SESSION['alert_message'] = ['type'=>'success','message'=>'Contact sent successfully! Admin will reply soon.'];
} catch (Exception $e) {
    $_SESSION['alert_message'] = ['type'=>'danger','message'=>"Unable to send email: {$mail->ErrorInfo}"];
}

header('Location: contact.php');
exit;
