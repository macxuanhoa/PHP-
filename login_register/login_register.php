<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    $dashboard_url = ($_SESSION['role'] === 'admin') ? '../admin/dashboard.php' : '../student/dashboard.php';
    header("Location: $dashboard_url");
    exit;
}

$error_login = '';
$error_register = '';

// --- LOGIN (UPDATED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['login_email']);
    $password = trim($_POST['login_password']);

    /* === (NEW) Add @gmail.com validation logic for Login === */
    $email_domain = '@gmail.com';
    $is_gmail = (substr(strtolower($email), -strlen($email_domain)) === $email_domain);
    /* === (END) === */

    if (empty($email) || empty($password)) {
        $error_login = "⚠️ Please enter both email and password.";

        /* === (NEW) Add $is_gmail validation for Login === */
    } elseif (!$is_gmail && !empty($email)) {
        $error_login = "⚠️ This system only accepts @gmail.com accounts.";
        /* === (END) === */
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, avatar FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];

                $dashboard_url = ($user['role'] === 'admin') ? '../admin/dashboard.php' : '../student/dashboard.php';
                header("Location: $dashboard_url");
                exit;
            } else {
                $error_login = "❌ Email or password is incorrect.";
            }
        } catch (PDOException $e) {
            $error_login = "⚠️ System error. Please try again later.";
            error_log("Login PDOException: " . $e->getMessage());
        }
    }
}

// --- REGISTER (Keep your existing @gmail.com code) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['reg_name']);
    $email = trim($_POST['reg_email']);
    $password = trim($_POST['reg_password']);
    $password2 = trim($_POST['reg_password2']);

    /* === (REQUIRE @GMAIL.COM) === */
    $email_domain = '@gmail.com';
    $is_gmail = (substr(strtolower($email), -strlen($email_domain)) === $email_domain);
    /* === (END) === */

    if (empty($name) || empty($email) || empty($password) || empty($password2)) {
        $error_register = "⚠️ Please fill in all fields.";

        /* === (REQUIRE @GMAIL.COM) Add $is_gmail validation condition === */
    } elseif (!$is_gmail && !empty($email)) {
        $error_register = "⚠️ You must use a Google email (@gmail.com).";
        /* === (END) === */
    } elseif ($password !== $password2) {
        $error_register = "⚠️ Confirmation password does not match.";
    } elseif (strlen($password) < 6) {
        $error_register = "⚠️ Password must be at least 6 characters.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_register = "⚠️ This email is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, 'student']);

                header("Location: login_register.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            $error_register = "⚠️ System error. Please try again later.";
            error_log("Register PDOException: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - Student Portal</title>
    
    <link rel="stylesheet" href="../login_register/login_register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container" id="container">

        <div class="form-container register-container" id="registerContainer">
            <form method="POST" action="login_register.php" novalidate>
                <h1>Create Account</h1>
                <input type="text" name="reg_name" placeholder="Your Name" required value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>">
                <input type="email" name="reg_email" placeholder="Email (must be @gmail.com)" required value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                <input type="password" name="reg_password" placeholder="Password (at least 6 characters)" required>
                <input type="password" name="reg_password2" placeholder="Confirm Password" required>
                <button type="submit" name="register">Register</button>
                <a href="#" class="mobile-toggle" id="showLogin">Already have an account? Login</a>
            </form>
        </div>

        <div class="form-container login-container" id="loginContainer">
            <form method="POST" action="login_register.php" novalidate>
                <h1>Login</h1>
                <input type="email" name="login_email" placeholder="Email (@gmail.com)" required value="<?= htmlspecialchars($_POST['login_email'] ?? '') ?>">
                <input type="password" name="login_password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <a href="#" class="mobile-toggle" id="showRegister">Don't have an account? Register</a>
            </form>
        </div>

        <div class="overlay-container" id="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>If you already have an account, please login to continue.</p>
                    <button class="ghost" id="loginButton">Login</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello!</h1>
                    <p>Don't have an account? Register now to start your journey.</p>
                    <button class="ghost" id="registerButton">Register</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- 1. Display toast (Keep as is) ---
        function showToast(message, type) {
            if (!message) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        // --- 2. Run all code after page has loaded ---
        document.addEventListener('DOMContentLoaded', () => {

            const container = document.getElementById('container');
            const registerButton = document.getElementById('registerButton');
            const loginButton = document.getElementById('loginButton');
            const showRegister = document.getElementById('showRegister');
            const showLogin = document.getElementById('showLogin');
            const loginContainer = document.getElementById('loginContainer');
            const registerContainer = document.getElementById('registerContainer');

            registerButton?.addEventListener('click', () => container.classList.add('right-panel-active'));
            loginButton?.addEventListener('click', () => container.classList.remove('right-panel-active'));
            showRegister?.addEventListener('click', e => { e.preventDefault(); loginContainer.style.display='none'; registerContainer.style.display='flex'; });
            showLogin?.addEventListener('click', e => { e.preventDefault(); loginContainer.style.display='flex'; registerContainer.style.display='none'; });

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = this.querySelectorAll('input[required]');
                    let empty = false;
                    inputs.forEach(i => { if (i.value.trim() === '') empty = true; });
                    if (empty) {
                        e.preventDefault();
                        showToast('⚠️ Please fill in all information.', 'error');
                    }
                });
            });

            function updateResponsiveState() {
                if (window.innerWidth > 768) {
                    loginContainer.style.display='flex';
                    registerContainer.style.display='flex';
                } else {
                    if (!container.classList.contains('right-panel-active')) {
                        loginContainer.style.display='flex';
                        registerContainer.style.display='none';
                    } else {
                        loginContainer.style.display='none';
                        registerContainer.style.display='flex';
                    }
                }
            }

            <?php if (!empty($error_login)): ?>
                showToast(<?= json_encode($error_login) ?>, 'error');
            <?php endif; ?>

            <?php if (!empty($error_register)): ?>
                container.classList.add('right-panel-active');
                showToast(<?= json_encode($error_register) ?>, 'error');
            <?php endif; ?>

            <?php if (isset($_GET['show']) && $_GET['show'] === 'register'): ?>
                container.classList.add('right-panel-active');
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                showToast('🎉 Registration successful! You can login now.', 'success');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.pathname);
                }
            <?php endif; ?>

            updateResponsiveState();
            window.addEventListener('resize', updateResponsiveState);
        });
    </script>
</body>
</html>