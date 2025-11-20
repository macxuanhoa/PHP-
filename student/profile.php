<?php
require_once __DIR__ . '/../includes/session_manager.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register/login_register.php");
    exit;
}
require_once __DIR__ . '/../includes/config.php';
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle avatar upload (AJAX)
if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
    header('Content-Type: application/json');
    
    $avatarFile = $_FILES['avatar'];
    $response = ['success' => false, 'message' => '', 'avatar_path' => ''];
    
    if ($avatarFile['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg','image/png','image/gif'];
        if (in_array($avatarFile['type'], $allowedTypes)) {
            $ext = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
            $newFileName = 'avatar_' . $user_id . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($avatarFile['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                if ($stmt->execute([$newFileName, $user_id])) {
                    $_SESSION['avatar'] = $newFileName; // Update session for persistence
                    $response['success'] = true;
                    $response['message'] = "Avatar updated successfully!";
                    $response['avatar_path'] = "../uploads/avatars/" . $newFileName;
                } else {
                    $response['message'] = "Failed to update avatar in database.";
                }
            } else {
                $response['message'] = "Failed to upload avatar file.";
            }
        } else {
            $response['message'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
    } else {
        $response['message'] = "Upload error. Please try again.";
    }
    
    echo json_encode($response);
    exit;
}

// Get current information
$stmt = $pdo->prepare("SELECT name, email, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch();

// Handle form update for info & password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $newName = trim($_POST['name']);
        $newEmail = trim($_POST['email']);
        
        if (empty($newName) || empty($newEmail)) {
            $error = "Name and Email cannot be empty.";
        } elseif ($newEmail === $currentUser['email'] && $newName === $currentUser['name']) {
            $error = "No changes detected. Please update at least one field.";
        } else {
            // Check if email already exists for another user
            if ($newEmail !== $currentUser['email']) {
                $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $emailCheck->execute([$newEmail, $user_id]);
                if ($emailCheck->fetch()) {
                    $error = "Email already exists. Please use a different email.";
                }
            }
            
            if (empty($error)) {
                $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                if ($updateStmt->execute([$newName, $newEmail, $user_id])) {
                    $_SESSION['username'] = $newName;
                    $success = "Profile updated successfully!";
                    $currentUser['name'] = $newName;
                    $currentUser['email'] = $newEmail;
                } else $error = "Failed to update profile.";
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } else {
            $pwStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $pwStmt->execute([$user_id]);
            $userPasswordHash = $pwStmt->fetchColumn();

            if (password_verify($currentPassword, $userPasswordHash)) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePwStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($updatePwStmt->execute([$newPasswordHash, $user_id])) {
                    $success = "Password changed successfully!";
                } else $error = "Failed to change password.";
            } else $error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile - Student Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/taskbar.css">

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
  --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  min-height: 100vh;
  margin: 0;
  padding: 0;
  position: relative;
  transition: background 0.5s cubic-bezier(0.4, 0, 0.2, 1), 
              color 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  overflow-x: hidden;
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
  --gradient-primary: linear-gradient(135deg, var(--accent-a), var(--accent-b));
  --gradient-secondary: linear-gradient(135deg, var(--accent-b), var(--accent-c));
  --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.05));
}

/* Main Container */
.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  min-height: calc(100vh - 80px);
}

/* Header Section */
.profile-header {
  background: var(--white);
  border-radius: var(--radius);
  padding: 2.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
}

.profile-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 120px;
  background: var(--gradient-primary);
  z-index: 0;
  transition: background 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

body.dark-mode .profile-header {
  background: rgba(25, 30, 40, 0.95);
}

body.dark-mode .profile-header::before {
  background: linear-gradient(135deg, #00a896 0%, #1a2f4f 100%);
}

.profile-header-content {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.profile-header-text {
  flex: 1;
  text-align: left;
}

.profile-title {
  color: white;
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
}

.profile-subtitle {
  color: rgba(255,255,255,0.9);
  margin: 0.5rem 0 0 0;
  font-size: 1.1rem;
}

/* Avatar Section */
.avatar-section {
  text-align: center;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.avatar-wrapper {
  position: relative;
  display: inline-block;
}

.profile-avatar {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 5px solid var(--white);
  box-shadow: var(--shadow-lg);
  transition: var(--transition);
}

.profile-avatar:hover {
  transform: scale(1.05);
}

.avatar-upload-input {
  display: none;
}

/* Avatar Upload Overlay */
.avatar-upload-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.7);
  border-radius: 50%;
  opacity: 0;
  transition: all 0.3s ease;
  pointer-events: none;
}

.avatar-wrapper:hover .avatar-upload-overlay {
  opacity: 1;
  pointer-events: all;
}

.avatar-upload-button {
  background: linear-gradient(135deg, var(--primary-color), #16a085);
  color: white;
  border: 2px solid white;
  border-radius: 25px;
  padding: 0.6rem 1.2rem;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  transform: scale(0.9);
}

.avatar-wrapper:hover .avatar-upload-button {
  transform: scale(1);
}

.avatar-upload-button:hover {
  background: var(--gradient-secondary);
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.avatar-upload-button i {
  font-size: 0.75rem;
}

body.dark-mode .avatar-upload-overlay {
  background: rgba(0,0,0,0.8);
}

body.dark-mode .avatar-upload-button {
  background: var(--gradient-primary);
  border-color: rgba(255,255,255,0.9);
}

/* Avatar Preview - Inline Subtle Design */
.avatar-preview-inline {
  position: absolute;
  top: 50%;
  left: 100%;
  transform: translateY(-50%);
  margin-left: 1rem;
  z-index: 10000;
  opacity: 0;
  transform: translateY(-50%) translateX(10px);
  transition: all 0.3s ease;
  pointer-events: none;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(52, 152, 219, 0.3);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  padding: 0.5rem;
}

.avatar-preview-inline.show {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
  pointer-events: all;
}

body.dark-mode .avatar-preview-inline {
  background: rgba(30, 30, 30, 0.95);
  border-color: rgba(52, 152, 219, 0.5);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.preview-container {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 320px;
  max-width: 400px;
  position: relative;
  overflow: hidden;
}

.preview-image {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--accent-a);
  flex-shrink: 0;
}

.preview-info {
  flex: 1;
  min-width: 0; /* Prevent text overflow */
}

.preview-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text);
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.preview-info p {
  margin: 0 0 0.75rem 0;
  font-size: 0.8rem;
  color: var(--muted);
  line-height: 1.3;
  word-wrap: break-word;
}

.preview-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: nowrap;
}

body.dark-mode .preview-info h4 {
  color: var(--text);
}

body.dark-mode .preview-info p {
  color: var(--muted);
}

/* Content Grid */
.profile-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

/* Form Cards */
.form-card {
  background: var(--card);
  border-radius: var(--radius);
  padding: 2rem;
  box-shadow: var(--shadow);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.1);
}

body.dark-mode .form-card {
  background: rgba(30, 35, 45, 0.95);
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 8px 32px rgba(0,0,0,0.5);
}

.form-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

body.dark-mode .form-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 50px rgba(0,0,0,0.7);
}

.form-card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--border-color);
}

.form-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.info-card .form-card-icon {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.password-card .form-card-icon {
  background: linear-gradient(135deg, #f093fb, #f5576c);
  color: white;
}

.form-card-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--accent-b);
  margin: 0;
  transition: color 0.3s ease;
}

body.dark-mode .form-card-title {
  color: #00e5cc;
  text-shadow: 0 0 10px rgba(0, 229, 204, 0.3);
}

.form-card-description {
  color: var(--muted);
  margin: 0.5rem 0 0 0;
  font-size: 0.95rem;
  transition: color 0.3s ease;
}

body.dark-mode .form-card-description {
  color: rgba(255, 255, 255, 0.6);
}

/* Form Elements */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

body.dark-mode .form-label {
  color: var(--text);
}

.form-control {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--border-color);
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: var(--card);
  color: var(--text);
}

.form-control:focus {
  outline: none;
  border-color: var(--accent-a);
  box-shadow: 0 0 0 3px rgba(0, 191, 178, 0.1);
  transform: translateY(-1px);
}

body.dark-mode .form-control {
  background: rgba(20, 25, 35, 0.6);
  color: rgba(255, 255, 255, 0.9);
  border-color: rgba(255,255,255,0.15);
}

body.dark-mode .form-control:focus {
  border-color: #00e5cc;
  background: rgba(30, 35, 45, 0.8);
  box-shadow: 0 0 0 3px rgba(0, 229, 204, 0.2);
  transform: translateY(-1px);
}

/* Buttons */
.btn {
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: var(--gradient-primary);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(26, 188, 156, 0.3);
}

.btn-success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(16,185,129,0.3);
}

.btn-warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.btn-warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(245,158,11,0.3);
}

.btn-block {
  width: 100%;
  justify-content: center;
}

/* Form Actions */
.form-actions {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border-color);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

.btn-secondary {
  background: linear-gradient(135deg, var(--muted), #6b7280);
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(107,114,128,0.3);
}

/* Button state transitions */
.btn {
  transition: all 0.3s ease;
}

body.dark-mode .form-actions {
  border-top-color: var(--dark-border-color, #3e4042);
}

/* Bottom Notifications */
.bottom-notification {
  position: fixed;
  bottom: 20px;
  right: 20px;
  left: 20px;
  max-width: 400px;
  margin: 0 auto;
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  z-index: 9999;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s ease;
}

.bottom-notification.show {
  transform: translateY(0);
  opacity: 1;
}

.bottom-notification-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
}

.bottom-notification-success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.bottom-notification-error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.bottom-notification-success i,
.bottom-notification-error i {
  font-size: 1.25rem;
}

body.dark-mode .bottom-notification {
  background: var(--card);
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: var(--white);
  padding: 1rem 1.5rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 300px;
  transform: translateX(400px);
  transition: transform 0.3s ease;
  z-index: 9999;
}

body.dark-mode .toast-notification {
  background: rgba(255,255,255,0.95);
}

.toast-notification.show {
  transform: translateX(0);
}

.toast-icon {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.toast-success .toast-icon {
  background: #10b981;
  color: white;
}

.toast-error .toast-icon {
  background: #ef4444;
  color: white;
}

.toast-message {
  flex: 1;
  font-weight: 500;
  color: var(--text);
}

body.dark-mode .toast-message {
  color: var(--text);
}

/* Responsive Design */
@media (max-width: 768px) {
  .profile-container {
    padding: 1rem;
  }
  
  .profile-header {
    padding: 1.5rem;
  }
  
  .profile-header-content {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .profile-content {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .form-card {
    padding: 1.5rem;
  }
}

/* Loading Spinner */
.spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="profile-container">
  <!-- Profile Header -->
  <div class="profile-header">
    <div class="profile-header-content">
      <div class="avatar-section">
        <div class="avatar-wrapper">
          <img id="profile-avatar" 
               src="../uploads/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? $currentUser['avatar'] ?? 'default.png') ?>" 
               alt="Profile Avatar" 
               class="profile-avatar">
          
          <!-- Professional Avatar Button -->
          <div class="avatar-upload-overlay">
            <button id="avatarBtn" class="avatar-upload-button" onclick="document.getElementById('avatarInput').click()">
              <i class="fa-solid fa-camera"></i>
              <span>Change Avatar</span>
            </button>
            <input type="file" id="avatarInput" class="avatar-upload-input" accept="image/*">
          </div>
        </div>
        
        <!-- Avatar Preview and Confirmation - Positioned outside wrapper -->
        <div id="avatarPreview" class="avatar-preview-inline" style="display: none;">
          <div class="preview-container">
            <img id="previewImage" src="" alt="Preview" class="preview-image">
            <div class="preview-info">
              <h4>New Avatar Preview</h4>
              <p>Confirm to change your avatar</p>
              <div class="preview-actions">
                <button id="confirmAvatarBtn" class="btn btn-success btn-sm" disabled>
                  <i class="fa-solid fa-check"></i> Confirm
                </button>
                <button id="cancelAvatarBtn" class="btn btn-secondary btn-sm">
                  <i class="fa-solid fa-times"></i> Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="profile-header-text">
        <h1 class="profile-title">User Profile</h1>
        <p class="profile-subtitle">Manage your account settings and preferences</p>
      </div>
    </div>
  </div>

  <!-- Profile Content -->
  <div class="profile-content">
    <!-- Information Card -->
    <div class="form-card info-card">
      <div class="form-card-header">
        <div class="form-card-icon">
          <i class="fa-solid fa-user"></i>
        </div>
        <div>
          <h2 class="form-card-title">Personal Information</h2>
          <p class="form-card-description">Update your personal details</p>
        </div>
      </div>
      
      <form id="infoForm" method="POST">
        <div class="form-group">
          <label class="form-label" for="name">
            <i class="fa-solid fa-user"></i> Full Name
          </label>
          <input type="text" 
                 class="form-control" 
                 id="name" 
                 name="name" 
                 value="<?= htmlspecialchars($currentUser['name']) ?>" 
                 required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="email">
            <i class="fa-solid fa-envelope"></i> Email Address
          </label>
          <input type="email" 
                 class="form-control" 
                 id="email" 
                 name="email" 
                 value="<?= htmlspecialchars($currentUser['email']) ?>" 
                 required>
        </div>
        
        <div class="form-actions">
          <button type="button" id="confirmUpdateBtn" class="btn btn-success btn-block" disabled>
            <i class="fa-solid fa-check-circle"></i> Confirm Update
          </button>
          <button type="submit" name="update_name" id="submitUpdateBtn" style="display: none;">
          </button>
        </div>
      </form>
    </div>

    <!-- Password Card -->
    <div class="form-card password-card">
      <div class="form-card-header">
        <div class="form-card-icon">
          <i class="fa-solid fa-key"></i>
        </div>
        <div>
          <h2 class="form-card-title">Change Password</h2>
          <p class="form-card-description">Keep your account secure</p>
        </div>
      </div>
      
      <form id="passwordForm" method="POST">
        <div class="form-group">
          <label class="form-label" for="current_password">
            <i class="fa-solid fa-lock"></i> Current Password
          </label>
          <input type="password" 
                 class="form-control" 
                 id="current_password" 
                 name="current_password" 
                 required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="new_password">
            <i class="fa-solid fa-key"></i> New Password
          </label>
          <input type="password" 
                 class="form-control" 
                 id="new_password" 
                 name="new_password" 
                 required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="confirm_password">
            <i class="fa-solid fa-check"></i> Confirm New Password
          </label>
          <input type="password" 
                 class="form-control" 
                 id="confirm_password" 
                 name="confirm_password" 
                 required>
        </div>
        
        <div class="form-actions">
          <button type="button" id="confirmPasswordBtn" class="btn btn-warning btn-block" disabled>
            <i class="fa-solid fa-shield-alt"></i> Confirm Password Change
          </button>
          <button type="submit" name="change_password" id="submitPasswordBtn" style="display: none;">
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Taskbar -->
<?php include __DIR__ . '/includes/taskbar.php'; ?>

<!-- Toast Notification -->
<div id="toast" class="toast-notification">
  <div class="toast-icon">
    <i class="fa-solid fa-check"></i>
  </div>
  <div class="toast-message"></div>
</div>

<script>
// Toast notification function
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  const icon = toast.querySelector('.toast-icon i');
  const messageEl = toast.querySelector('.toast-message');
  
  toast.className = `toast-notification toast-${type}`;
  
  if (type === 'success') {
    icon.className = 'fa-solid fa-check';
  } else if (type === 'error') {
    icon.className = 'fa-solid fa-exclamation';
  }
  
  messageEl.textContent = message;
  toast.classList.add('show');
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}

// Bottom notification function
function showBottomNotification(message, type = 'success') {
  // Remove existing bottom notification if any
  const existingNotification = document.querySelector('.bottom-notification');
  if (existingNotification) {
    existingNotification.remove();
  }
  
  // Create new notification
  const notification = document.createElement('div');
  notification.className = `bottom-notification bottom-notification-${type}`;
  notification.innerHTML = `
    <div class="bottom-notification-content">
      <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
      <span>${message}</span>
    </div>
  `;
  
  // Add to page
  document.body.appendChild(notification);
  
  // Show with animation
  setTimeout(() => {
    notification.classList.add('show');
  }, 100);
  
  // Auto remove after 4 seconds
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 4000);
}

// Show PHP messages
<?php if($success): ?> toastManager.success("<?= htmlspecialchars($success) ?>"); <?php endif; ?>
<?php if($error): ?> toastManager.error("<?= htmlspecialchars($error) ?>"); <?php endif; ?>

// Avatar upload with preview and confirmation
let selectedAvatarFile = null;

document.getElementById('avatarInput').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    selectedAvatarFile = file;
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('avatarPreview');
      const previewImage = document.getElementById('previewImage');
      const confirmBtn = document.getElementById('confirmAvatarBtn');
      
      previewImage.src = e.target.result;
      preview.style.display = 'block';
      
      // Trigger animation
      setTimeout(() => {
        preview.classList.add('show');
      }, 10);
      
      confirmBtn.disabled = false;
    };
    reader.readAsDataURL(file);
  }
});

// Confirm avatar upload
document.getElementById('confirmAvatarBtn').addEventListener('click', function() {
  if (selectedAvatarFile) {
    const formData = new FormData();
    formData.append('avatar', selectedAvatarFile);
    formData.append('update_avatar', '1');
    
    // Show loading state
    this.innerHTML = '<div class="spinner"></div>';
    this.disabled = true;
    document.getElementById('cancelAvatarBtn').disabled = true;
    
    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update avatar image with new path and timestamp
        const avatarImg = document.getElementById('profile-avatar');
        avatarImg.src = data.avatar_path + '?' + new Date().getTime();
        
        // Update header avatar
        const headerAvatar = document.querySelector('.user-area .avatar');
        if (headerAvatar) {
          headerAvatar.innerHTML = `<img src="${data.avatar_path}?${new Date().getTime()}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        }
        
        // Hide preview with animation
        const preview = document.getElementById('avatarPreview');
        preview.classList.remove('show');
        setTimeout(() => {
          preview.style.display = 'none';
        }, 300);
        
        // Show bottom notification
        showBottomNotification('Avatar updated successfully!', 'success');
      } else {
        showBottomNotification(data.message || 'Avatar update failed!', 'error');
      }
    })
    .catch(error => {
      showBottomNotification('Avatar update failed!', 'error');
    })
    .finally(() => {
      // Reset buttons
      document.getElementById('confirmAvatarBtn').innerHTML = '<i class="fa-solid fa-check"></i> Confirm';
      document.getElementById('confirmAvatarBtn').disabled = true;
      document.getElementById('cancelAvatarBtn').disabled = false;
      
      // Clear file input
      document.getElementById('avatarInput').value = '';
      selectedAvatarFile = null;
    });
  }
});

// Cancel avatar upload
document.getElementById('cancelAvatarBtn').addEventListener('click', function() {
  const preview = document.getElementById('avatarPreview');
  preview.classList.remove('show');
  
  setTimeout(() => {
    preview.style.display = 'none';
  }, 300);
  
  document.getElementById('avatarInput').value = '';
  selectedAvatarFile = null;
  document.getElementById('confirmAvatarBtn').disabled = true;
});

// Form validation enhancements
document.getElementById('passwordForm').addEventListener('submit', function(e) {
  const newPassword = document.getElementById('new_password').value;
  const confirmPassword = document.getElementById('confirm_password').value;
  
  if (newPassword !== confirmPassword) {
    e.preventDefault();
    toastManager.error('New passwords do not match!');
    return false;
  }
  
  if (newPassword.length < 6) {
    e.preventDefault();
    toastManager.error('Password must be at least 6 characters long!');
    return false;
  }
});

// Form validation and confirmation buttons
document.addEventListener('DOMContentLoaded', function() {
  // Profile info form
  const nameInput = document.getElementById('name');
  const emailInput = document.getElementById('email');
  const confirmUpdateBtn = document.getElementById('confirmUpdateBtn');
  const submitUpdateBtn = document.getElementById('submitUpdateBtn');
  
  // Password form
  const currentPasswordInput = document.getElementById('current_password');
  const newPasswordInput = document.getElementById('new_password');
  const confirmPasswordInput = document.getElementById('confirm_password');
  const confirmPasswordBtn = document.getElementById('confirmPasswordBtn');
  const submitPasswordBtn = document.getElementById('submitPasswordBtn');
  
  // Store original values
  const originalName = nameInput.value;
  const originalEmail = emailInput.value;
  
  // Check for changes in profile info
  function checkProfileChanges() {
    const hasChanges = nameInput.value !== originalName || emailInput.value !== originalEmail;
    const isValid = nameInput.value.trim() !== '' && emailInput.value.trim() !== '' && emailInput.checkValidity();
    
    confirmUpdateBtn.disabled = !hasChanges || !isValid;
    
    if (hasChanges && isValid) {
      confirmUpdateBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirm Update';
      confirmUpdateBtn.classList.remove('btn-secondary');
      confirmUpdateBtn.classList.add('btn-success');
    } else if (!isValid) {
      confirmUpdateBtn.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Please fix errors';
      confirmUpdateBtn.classList.remove('btn-success');
      confirmUpdateBtn.classList.add('btn-secondary');
    }
  }
  
  // Check password form validity
  function checkPasswordValidity() {
    const hasAllFields = currentPasswordInput.value !== '' && 
                        newPasswordInput.value !== '' && 
                        confirmPasswordInput.value !== '';
    const passwordsMatch = newPasswordInput.value === confirmPasswordInput.value;
    const isLongEnough = newPasswordInput.value.length >= 6;
    
    const isValid = hasAllFields && passwordsMatch && isLongEnough;
    
    confirmPasswordBtn.disabled = !isValid;
    
    if (isValid) {
      confirmPasswordBtn.innerHTML = '<i class="fa-solid fa-shield-alt"></i> Confirm Password Change';
      confirmPasswordBtn.classList.remove('btn-secondary');
      confirmPasswordBtn.classList.add('btn-warning');
    } else {
      let message = 'Complete all fields';
      if (!passwordsMatch) message = 'Passwords do not match';
      else if (!isLongEnough) message = 'Password too short (min 6 chars)';
      
      confirmPasswordBtn.innerHTML = `<i class="fa-solid fa-exclamation-triangle"></i> ${message}`;
      confirmPasswordBtn.classList.remove('btn-warning');
      confirmPasswordBtn.classList.add('btn-secondary');
    }
  }
  
  // Event listeners for profile form
  nameInput.addEventListener('input', checkProfileChanges);
  emailInput.addEventListener('input', checkProfileChanges);
  
  // Event listeners for password form
  currentPasswordInput.addEventListener('input', checkPasswordValidity);
  newPasswordInput.addEventListener('input', checkPasswordValidity);
  confirmPasswordInput.addEventListener('input', checkPasswordValidity);
  
  // Confirmation button clicks
  confirmUpdateBtn.addEventListener('click', function() {
    if (!this.disabled) {
      // Double check if email already exists (client-side validation)
      if (emailInput.value !== originalEmail) {
        // Show confirmation dialog
        if (confirm('Are you sure you want to update your profile information?')) {
          submitUpdateBtn.click();
        }
      } else {
        submitUpdateBtn.click();
      }
    }
  });
  
  confirmPasswordBtn.addEventListener('click', function() {
    if (!this.disabled) {
      if (confirm('Are you sure you want to change your password?')) {
        submitPasswordBtn.click();
      }
    }
  });
  
  // Email validation feedback
  emailInput.addEventListener('blur', function() {
    if (this.value && !this.checkValidity()) {
      this.style.borderColor = 'var(--danger, #dc3545)';
    } else {
      this.style.borderColor = '';
    }
  });
});

// Add input focus effects
document.querySelectorAll('.form-control').forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.classList.add('focused');
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.classList.remove('focused');
  });
});
</script>

<?php include __DIR__ . '/includes/theme_manager.php'; ?>

</body>
</html>
