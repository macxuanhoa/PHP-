<?php
/**
 * Contact Form - Send email to admin using PHPMailer
 * Email: hoamxgcd220422@fpt.edu.vn
 */
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer via Composer
require_once __DIR__ . '/vendor/autoload.php';

$errors = [];
$success_message = '';
$previous_url = $_SESSION['previous_url'] ?? '/home/home.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if ($name === '') {
        $errors['name'] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required.';
    }
    if ($subject === '') {
        $errors['subject'] = 'Subject is required.';
    }
    if ($message === '') {
        $errors['message'] = 'Message is required.';
    }

    // Handle file attachment
    $attachmentPath = null;
    $attachmentName = null;
    
    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = __DIR__ . '/assets/uploads/contact_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $attachmentName = basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . time() . '_' . $attachmentName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $attachmentPath = $targetPath;
        } else {
            $errors['attachment'] = 'Failed to upload attachment.';
        }
    }

    // Send email if no errors
    if (empty($errors)) {
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hoamxgcd220422@fpt.edu.vn';
            $mail->Password   = 'amzwkwcytpumbwgt'; // App password (no spaces)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Email headers
            $mail->setFrom('hoamxgcd220422@fpt.edu.vn', 'Student Portal Contact');
            $mail->addAddress('hoamxgcd220422@fpt.edu.vn', 'Admin');
            $mail->addReplyTo($email, $name);

            // Attachment
            if ($attachmentPath) {
                $mail->addAttachment($attachmentPath, $attachmentName);
            }

            // Email content
            $mail->isHTML(true);
            $mail->Subject = '[Contact Form] ' . $subject;
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2 style='color: #4f46e5;'>New Contact Form Submission</h2>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'><strong>Name:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($name) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'><strong>Email:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($email) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'><strong>Subject:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($subject) . "</td>
                        </tr>
                    </table>
                    <h3 style='color: #333; margin-top: 20px;'>Message:</h3>
                    <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #4f46e5;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

            $mail->send();
            $success_message = '✓ Your message has been sent successfully! We will get back to you soon.';
            
            // Clear form
            $_POST = [];
        } catch (Exception $e) {
            $errors['mail'] = 'Message could not be sent. Error: ' . $mail->ErrorInfo;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin - Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .contact-wrapper {
            width: 100%;
            max-width: 650px;
        }

        .contact-container {
            background: #020617;
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .contact-header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .contact-header p {
            color: #9ca3af;
            font-size: 0.95rem;
        }

        .success-box {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2);
        }

        .success-box i {
            font-size: 1.25rem;
        }

        .error-box {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);
        }

        .error-box i {
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #e5e7eb;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #374151;
            background: #0f172a;
            color: #e5e7eb;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .error-text {
            color: #f87171;
            font-size: 0.85rem;
            margin-top: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .error-text i {
            font-size: 0.75rem;
        }

        .btn-row {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-primary {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            border: none;
            background: linear-gradient(to right, #4f46e5, #7c3aed);
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #374151;
            background: transparent;
            color: #e5e7eb;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: #1f2937;
            border-color: #4b5563;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-wrapper input[type="file"] {
            cursor: pointer;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #4f46e5;
            background: #4f46e5;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            margin-right: 1rem;
            transition: all 0.2s ease;
        }

        .file-input-wrapper input[type="file"]::file-selector-button:hover {
            background: #4338ca;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .contact-container {
                padding: 1.5rem;
            }

            .contact-header h1 {
                font-size: 1.5rem;
            }

            .btn-row {
                flex-direction: column;
            }

            .btn-secondary {
                order: 2;
            }
        }
    </style>
</head>
<body>
    <div class="contact-wrapper">
        <div class="contact-container">
            <div class="contact-header">
                <h1><i class="fas fa-envelope"></i> Contact Admin</h1>
                <p>Have questions or need support? Send us a message and we'll get back to you soon.</p>
            </div>

            <?php if ($success_message): ?>
                <div class="success-box">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Please fix the errors below and try again.</span>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Enter your name"
                        value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['username'] ?? '')) ?>"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="error-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['name']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your.email@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['email'] ?? '')) ?>"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['email']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        placeholder="What is this about?"
                        value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['subject'])): ?>
                        <div class="error-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['subject']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="message"><i class="fas fa-comment-dots"></i> Message</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        rows="6"
                        placeholder="Type your message here..."
                    ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <?php if (!empty($errors['message'])): ?>
                        <div class="error-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['message']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group file-input-wrapper">
                    <label for="attachment"><i class="fas fa-paperclip"></i> Attachment (Optional)</label>
                    <input 
                        type="file" 
                        id="attachment" 
                        name="attachment" 
                        accept="image/*,application/pdf"
                    >
                    <?php if (!empty($errors['attachment'])): ?>
                        <div class="error-text">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($errors['attachment']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors['mail'])): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($errors['mail']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="btn-row">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Message</span>
                    </button>
                    <a href="<?= htmlspecialchars($previous_url) ?>" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
