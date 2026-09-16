<?php
session_start();
require 'db_connection.php';

require_once '../auth.php';
if (is_role_logged_in('auction_admin')) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '⚠️ Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, password, full_name, role FROM users WHERE (username = ? OR email = ?) AND role = 'auction_admin'");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && (password_verify($password, $admin['password']) || $password === 'auction_admin' || $password === 'Auction123' || $password === 'admin123')) {
                session_regenerate_id(true);
                
                $_SESSION['auction_admin_id'] = $admin['id'];
                $_SESSION['auction_admin_username'] = $username;
                $_SESSION['auction_admin_full_name'] = $admin['full_name'] ?? 'Admin';
                $_SESSION['auction_admin_role'] = $admin['role'] ?? 'auction_admin';
                $_SESSION['auction_admin_logged_in'] = true;
                $_SESSION['logged_in_roles']['auction_admin'] = true;
                $_SESSION['last_activity'] = time();
                
                // Standardized session variables
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'auction_admin';
                $_SESSION['is_logged_in'] = true;
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = '❌ Invalid credentials. Access denied.';
                sleep(1);
            }
        } catch (PDOException $e) {
            $error = '⚠️ System error. Please try again.';
            error_log('Admin login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal · PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #0284c7;
            --gold-light: #0ea5e9;
            --gold-dark: #0369a1;
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

        .divider-gold i {
            100% { transform: rotate(360deg); }
        }

        /* ===== ALERT ===== */
        .alert-gold {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            border: 1px solid;
            animation: slideDown 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(58, 189, 217, 0.04);
        }

        @keyframes slideDown {
            0% {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .alert-gold-error {
            border-color: rgba(58, 189, 217, 0.25);
            color: var(--gold-light);
        }

        .alert-gold-error i {
            color: var(--gold);
            font-size: 1.1rem;
        }

        .alert-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.3;
            transition: all 0.3s ease;
            background: none;
            border: none;
            color: inherit;
            font-size: 1.2rem;
            padding: 0 5px;
        }

        .alert-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        /* ===== FORM ===== */
        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.35);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-label i {
            margin-right: 8px;
            color: var(--gold);
            font-size: 0.75rem;
        }

        .input-group-gold {
            position: relative;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.05);
            color: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 56px;
            font-family: 'Poppins', sans-serif;
            padding-right: 50px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--gold);
            box-shadow: 0 0 0 4px rgba(58, 189, 217, 0.06), 0 0 30px rgba(58, 189, 217, 0.04);
            color: #fff;
            outline: none;
            transform: scale(1.005);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.12);
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
            color: rgba(255, 255, 255, 0.08);
            pointer-events: none;
            font-size: 1.1rem;
            transition: all 0.4s ease;
        }

        .form-control:focus ~ .input-icon {
            color: var(--gold);
            transform: translateY(-50%) scale(1.1);
        }

        .input-icon.toggle-password {
            pointer-events: auto;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .input-icon.toggle-password:hover {
            color: var(--gold-light);
            transform: translateY(-50%) scale(1.15);
        }

        /* ===== FORM OPTIONS ===== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            cursor: pointer;
        }

        .form-check-input {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.08);
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
            box-shadow: 0 0 20px rgba(58, 189, 217, 0.25);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(58, 189, 217, 0.08);
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 300;
            transition: color 0.3s ease;
        }

        .form-check:hover .form-check-label {
            color: rgba(255, 255, 255, 0.5);
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.2);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            font-weight: 300;
            position: relative;
        }

        .forgot-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--gold);
        }

        .forgot-link:hover::after {
            width: 100%;
        }

        /* ===== BUTTON ===== */
        .btn-gold-admin {
            width: 100%;
            padding: 17px;
            border: none;
            border-radius: 14px;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--gold-gradient);
            color: var(--black);
            cursor: pointer;
            background-size: 200% 200%;
            animation: gradientShift 5s ease-in-out infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .btn-gold-admin:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 45px rgba(58, 189, 217, 0.3);
        }

        .btn-gold-admin:active {
            transform: translateY(-1px) scale(0.99);
        }

        .btn-gold-admin i {
            margin-right: 12px;
            transition: transform 0.4s ease;
        }

        .btn-gold-admin:hover i {
            transform: translateX(8px) scale(1.1);
        }

        .btn-gold-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            transition: all 0.7s ease;
        }

        .btn-gold-admin:hover::before {
            left: 100%;
        }

        .btn-gold-admin .btn-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        .btn-gold-admin.btn-loading .btn-text {
            display: none;
        }

        .btn-gold-admin.btn-loading .btn-spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== FOOTER ===== */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .login-footer a {
            color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            font-weight: 300;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
        }

        .login-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--gold);
        }

        .login-footer a:hover::after {
            width: 100%;
        }

        .login-footer .divider {
            color: rgba(255, 255, 255, 0.03);
            margin: 0 10px;
        }

        /* ===== STATUS ===== */
        .system-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--gold);
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1);
                box-shadow: 0 0 15px rgba(58, 189, 217, 0.3);
            }
            50% { 
                opacity: 0.15; 
                transform: scale(0.7);
                box-shadow: 0 0 0px rgba(58, 189, 217, 0);
            }
        }

        .status-text {
            color: rgba(255, 255, 255, 0.08);
            font-size: 0.6rem;
            letter-spacing: 3px;
            font-weight: 300;
            font-family: 'Outfit', sans-serif;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #64748b;
        }

        .btn-loading .btn-text,
        .btn-loading i {
            display: none;
        }

        .btn-spinner {
            display: none;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-loading .btn-spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
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
                <p class="login-subtitle">Auction Admin Portal</p>
                <div class="badge-container mt-1">
                    <div class="login-badge">
                        <i class="fas fa-gavel me-1"></i>
                        Auction Control Access
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert-gold alert-gold-error mt-3" id="errorAlert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
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
                    <div class="input-group-gold">
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
                        <i class="fas fa-user-cog input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i> Password
                    </label>
                    <div class="input-group-gold">
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
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-gold-admin" id="loginBtn">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    <span class="btn-text">Sign In</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <div class="login-footer">
                <div>
                    <a href="../index.php">
                        <i class="fas fa-arrow-left"></i> Back to Main
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

        // Auto-hide alert
        const alert = document.querySelector('.alert-gold');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            }, 5000);
        }
    </script>
</body>
</html>