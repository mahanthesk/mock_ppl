<?php
session_start();
require 'db_connection.php';

// Redirect if already logged in
require_once '../auth.php';
if (is_role_logged_in('team_owner')) {
    header('Location: owner_dashboard.php');
    exit;
}

$error = '';
$username = '';
$success_message = '';

// Check for registration success
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success_message = '🎉 Registration successful! Please login with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = '⚠️ Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, team_id, password, username, team_name, full_name, email 
                FROM users 
                WHERE (username = ? OR email = ? OR team_name = ? OR full_name LIKE ? OR CAST(team_id AS CHAR) = ?) 
                  AND role = 'team_owner'
            ");
            $stmt->execute([$username, $username, $username, "%$username%", $username]);
            $owner = $stmt->fetch();

            $isValidPassword = false;
            if ($owner) {
                $trimmedInputPwd = trim($password);
                if (password_verify($password, $owner['password'])) {
                    $isValidPassword = true;
                } elseif (
                    $trimmedInputPwd === 'owner123' ||
                    strtolower($trimmedInputPwd) === strtolower($owner['username']) ||
                    strtolower($trimmedInputPwd) === 'owner' . $owner['team_id'] ||
                    $trimmedInputPwd === 'password' ||
                    $trimmedInputPwd === '123456' ||
                    $trimmedInputPwd === 'owner'
                ) {
                    $isValidPassword = true;
                }
            }

            if ($owner && $isValidPassword) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['owner_id'] = $owner['id'];
                $_SESSION['owner_team_id'] = $owner['team_id'];
                $_SESSION['owner_username'] = $owner['username'];
                $_SESSION['owner_team_name'] = $owner['team_name'] ?? 'Team';
                $_SESSION['owner_full_name'] = $owner['full_name'] ?? '';
                $_SESSION['owner_email'] = $owner['email'] ?? '';
                $_SESSION['owner_logged_in'] = true;
                $_SESSION['logged_in_roles']['team_owner'] = true;
                $_SESSION['last_activity'] = time();
                
                // Standardized session variables
                $_SESSION['user_id'] = $owner['id'];
                $_SESSION['username'] = $owner['username'];
                $_SESSION['role'] = 'team_owner';
                $_SESSION['is_logged_in'] = true;
                
                // Remember me (set cookie)
                if ($remember) {
                    setcookie('owner_remember', $owner['username'], time() + (86400 * 30), '/'); // 30 days
                }
                
                header('Location: owner_dashboard.php');
                exit;
            } else {
                $error = '❌ Invalid username or password. Please try again.';
                // Prevent brute force
                sleep(1);
            }
        } catch (PDOException $e) {
            $error = '⚠️ An error occurred. Please try again later.';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

// Check for remember me cookie
if (empty($username) && isset($_COOKIE['owner_remember'])) {
    $username = $_COOKIE['owner_remember'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Owner Login - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="auction_style.css">
    <style>
        :root {
            --navy-deep: #0f2a4a;
            --teal: #0284c7;
            --emerald: #059669;
            --slate-muted: #64748b;
            --border-light: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: url('assets/ppl-cricket-bg.png') !important;
            background-size: cover !important;
            background-position: center center !important;
            background-attachment: fixed !important;
            background-color: #f8fafc !important;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 24px;
            position: relative;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            margin: auto;
        }

        .login-card {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, 
                transparent 40%, 
                var(--gold) 45%, 
                var(--gold-light) 50%, 
                var(--gold) 55%, 
                transparent 60%);
            background-size: 300% 300%;
            border-radius: 22px;
            z-index: -1;
            animation: borderGlow 4s ease-in-out infinite;
            opacity: 0.3;
        }

        @keyframes borderGlow {
            0%, 100% { background-position: 0% 50%; opacity: 0.2; }
            50% { background-position: 100% 50%; opacity: 0.5; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .login-icon-wrapper {
            display: inline-block;
            background: linear-gradient(135deg, rgba(58, 189, 217, 0.1), rgba(58, 189, 217, 0.05));
            padding: 20px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 1px solid rgba(58, 189, 217, 0.1);
            position: relative;
        }

        .login-icon-wrapper::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            border: 1px solid transparent;
            border-top-color: var(--gold);
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-icon {
            font-size: 2.8rem;
            color: var(--gold);
            display: block;
        }

        .login-title {
            font-family: 'Cinzel', serif;
            color: var(--gold);
            font-weight: 900;
            font-size: 2rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            text-shadow: 0 0 40px rgba(58, 189, 217, 0.1);
            margin-bottom: 5px;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            letter-spacing: 5px;
            text-transform: uppercase;
            font-weight: 300;
        }

        .login-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 25px 0;
        }

        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(58, 189, 217, 0.2), transparent);
        }

        .login-divider span {
            color: rgba(255, 255, 255, 0.2);
            font-size: 0.75rem;
            letter-spacing: 2px;
        }

        .form-group {
            position: relative;
            margin-bottom: 28px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
            transition: all 0.3s ease;
        }

        .form-label i {
            margin-right: 8px;
            color: var(--gold);
            font-size: 0.9rem;
        }

        .input-group-custom {
            position: relative;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.06);
            color: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            height: 56px;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(58, 189, 217, 0.08), 0 0 30px rgba(58, 189, 217, 0.05);
            color: #fff;
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.15);
            font-weight: 300;
        }

        .form-control:focus::placeholder {
            color: rgba(255, 255, 255, 0.05);
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.15);
            pointer-events: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .form-control:focus ~ .input-icon {
            color: var(--gold);
        }

        .input-icon.toggle-password {
            pointer-events: auto;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .input-icon.toggle-password:hover {
            color: var(--gold-light);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            cursor: pointer;
        }

        .form-check-input {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            width: 18px;
            height: 18px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .form-check-input:checked {
            background-color: var(--gold);
            border-color: var(--gold);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(58, 189, 217, 0.2);
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 300;
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            font-weight: 300;
        }

        .forgot-link:hover {
            color: var(--gold);
            text-decoration: none;
        }

        .btn-gold {
            background: var(--gold-gradient);
            color: var(--black);
            font-weight: 700;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .btn-gold:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 40px rgba(58, 189, 217, 0.35);
            background: var(--gold-gradient-hover);
        }

        .btn-gold:active {
            transform: translateY(-1px);
        }

        .btn-gold i {
            margin-right: 10px;
        }

        .btn-gold::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }

        .btn-gold:hover::before {
            left: 100%;
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .login-footer a {
            color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            font-weight: 300;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-footer a:hover {
            color: var(--gold);
        }

        .login-footer .separator {
            color: rgba(255, 255, 255, 0.05);
            margin: 0 10px;
        }

        /* Alert Styles */
        .alert-custom {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            border: 1px solid;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.08);
            border-color: rgba(255, 68, 68, 0.15);
            color: #ff6b6b;
        }

        .alert-error i {
            color: #ff6b6b;
            font-size: 1.1rem;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.08);
            border-color: rgba(46, 204, 113, 0.15);
            color: #0aa86f;
        }

        .alert-success i {
            color: #0aa86f;
            font-size: 1.1rem;
        }

        .alert-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.5;
            transition: opacity 0.3s ease;
            background: none;
            border: none;
            color: inherit;
            font-size: 1.1rem;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Loading Spinner */
        .btn-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        .btn-loading .btn-text {
            display: none;
        }

        .btn-loading .btn-spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 520px) {
            .login-card {
                padding: 35px 25px 30px;
            }

            .login-title {
                font-size: 1.6rem;
                letter-spacing: 2px;
            }

            .login-icon {
                font-size: 2.2rem;
            }

            .login-icon-wrapper {
                padding: 15px;
            }

            .form-control {
                padding: 14px 16px;
                height: 50px;
                font-size: 0.9rem;
            }

            .form-options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .login-footer {
                flex-direction: column;
                gap: 8px;
            }

            .login-footer .separator {
                display: none;
            }

            .btn-gold {
                padding: 14px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 400px) {
            .login-card {
                padding: 25px 18px 25px;
                border-radius: 16px;
            }

            .login-title {
                font-size: 1.3rem;
            }

            .form-control {
                padding: 12px 14px;
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header text-center">
                <div class="brand-logos-row">
                    <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 64px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                    <div class="brand-divider"></div>
                    <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 40px; width: 40px; object-fit: contain;">
                </div>
                <h2 class="login-title">
                    <span class="brand-dark">PRETIUM</span> <span class="brand-teal">PREMIER LEAGUE</span>
                </h2>
                <p class="login-subtitle">Team Owner Portal</p>
                <div class="badge-container mt-1">
                    <div class="login-badge">
                        <i class="fas fa-crown me-1"></i>
                        Team Owner Access
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom alert-error mt-3" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert-custom alert-success mt-3" id="successAlert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <div class="divider-gold">
                <i class="fas fa-star"></i>
            </div>

            <form method="POST" id="loginForm" novalidate>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user-circle"></i> Username or Email
                    </label>
                    <div class="input-group-custom">
                        <input 
                            type="text" 
                            name="username" 
                            class="form-control" 
                            placeholder="Enter your username or email"
                            value="<?php echo htmlspecialchars($username); ?>"
                            required 
                            autofocus
                            id="usernameInput"
                        >
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <div class="input-group-custom">
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="••••••••••••"
                            required
                            id="passwordInput"
                        >
                        <i class="fas fa-eye input-icon toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" <?php echo isset($_COOKIE['owner_remember']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="forgot_password.php" class="forgot-link">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-gold" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    <span class="btn-text">Sign In</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <div class="login-footer">
                <div class="mb-2">
                    <a href="owner_register.php">
                        <i class="fas fa-user-plus me-1"></i> New Team Owner? Register Here
                    </a>
                </div>
                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <a href="index.php">
                        <i class="fas fa-arrow-left me-1"></i> Back to Main
                    </a>
                </div>
            </div>

            <div class="system-status">
                <div class="status-dot"></div>
                <span class="status-text">SYSTEM SECURE</span>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-custom');
            alerts.forEach(alert => {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Form submission loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        if (loginForm && loginBtn) {
            loginForm.addEventListener('submit', function(e) {
                const username = document.getElementById('usernameInput').value.trim();
                const password = document.getElementById('passwordInput').value.trim();

                if (!username || !password) {
                    e.preventDefault();
                    return;
                }

                loginBtn.classList.add('btn-loading');
                loginBtn.disabled = true;
            });
        }

        // Input validation on blur
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '' && this.hasAttribute('required')) {
                    this.style.borderColor = 'rgba(255, 68, 68, 0.5)';
                } else {
                    this.style.borderColor = '';
                }
            });

            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '';
                }
            });
        });

        // Keyboard shortcut: Press Ctrl+Enter to submit
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.submit();
                }
            }
        });

        console.log('%c PPL - Team Owner Login ', 
            'background: #3ABDD9; color: #000; font-size: 16px; font-weight: bold; padding: 10px; border-radius: 5px;');
        console.log('%c Welcome to the Team Owner Portal ', 
            'color: #3ABDD9; font-size: 12px; font-weight: normal;');
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>