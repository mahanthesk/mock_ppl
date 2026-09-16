<?php
session_start();
require 'db_connection.php';

require_once 'auth.php';
if (is_role_logged_in('registration_admin')) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ? AND role = 'registration_admin'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        $_SESSION['reg_admin_logged_in'] = true;
        $_SESSION['logged_in_roles']['registration_admin'] = true;
        // Standardized session variables
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'registration_admin';
        $_SESSION['is_logged_in'] = true;
        
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Admin Login - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
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
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            padding: 42px 38px 36px;
            box-shadow: 0 10px 35px -5px rgba(15, 23, 42, 0.08), 0 0 1px rgba(15, 23, 42, 0.1);
            position: relative;
            transition: all 0.3s ease;
        }

        .brand-logos-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 20px;
        }

        .brand-divider {
            width: 1.5px;
            height: 38px;
            background: #cbd5e1;
        }

        .login-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.42rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .login-title .brand-dark { color: #0f2a4a; }
        .login-title .brand-teal { color: #0284c7; }

        .login-subtitle {
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 16px;
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e6fcf5;
            color: #0d9488;
            border: 1px solid #99f6e4;
            font-family: 'Outfit', sans-serif;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 50px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .divider-gold {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 22px 0;
            color: #94a3b8;
        }

        .divider-gold::before,
        .divider-gold::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-label {
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
            font-family: 'Outfit', sans-serif;
        }

        .form-label i {
            margin-right: 6px;
            color: #0284c7;
        }

        .input-group-custom {
            position: relative;
        }

        .form-control-custom {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            color: #0f172a;
            border-radius: 12px;
            padding: 14px 44px 14px 16px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-control-custom:focus {
            background: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        .input-icon-custom {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.05rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control-custom:focus ~ .input-icon-custom {
            color: #0284c7;
        }

        .toggle-password-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.05rem;
            padding: 4px;
            transition: color 0.2s ease;
        }

        .toggle-password-btn:hover {
            color: #0284c7;
        }

        .btn-signin-pill {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.92rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transition: all 0.25s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-signin-pill:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
            color: #ffffff;
        }

        .login-footer-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }

        .login-footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-footer-links a:hover {
            color: #0284c7;
        }

        .system-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
        }

        .status-text {
            font-family: 'Outfit', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #64748b;
        }
    </style>
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
                <p class="login-subtitle">Registration Admin Portal</p>
                <div class="badge-container mt-1">
                    <div class="login-badge">
                        <i class="fas fa-clipboard-check me-1"></i>
                        Registration Control Access
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mt-3 py-2 d-flex align-items-center gap-2" style="border-radius: 12px; font-size: 0.88rem;">
                    <i class="fas fa-exclamation-circle text-danger"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="divider-gold">
                <i class="fas fa-star" style="font-size: 0.75rem; color: #94a3b8;"></i>
            </div>

            <form method="POST" id="loginForm" novalidate>
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user-circle"></i> Username
                    </label>
                    <div class="input-group-custom">
                        <input 
                            type="text" 
                            name="username" 
                            id="username"
                            class="form-control-custom" 
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($username); ?>"
                            required 
                            autofocus
                        >
                        <i class="fas fa-user-cog input-icon-custom"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <div class="input-group-custom">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-control-custom" 
                            placeholder="••••••••••••"
                            required
                        >
                        <button type="button" class="toggle-password-btn" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-signin-pill" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <div class="login-footer-links">
                <a href="index.html">
                    <i class="fas fa-arrow-left me-1"></i> Back to Main
                </a>
            </div>

            <div class="system-status">
                <div class="status-dot"></div>
                <span class="status-text">SYSTEM SECURE</span>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');

        if (togglePassword && passwordInput && togglePasswordIcon) {
            togglePassword.addEventListener('click', function() {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                togglePasswordIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        }
    </script>
</body>
</html>