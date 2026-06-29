<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;

$controller = new UserController();
$controller->requireGuest();

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $result = $controller->login();
    
    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit();
    } else {
        $error = $result['message'];
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $result = $controller->register();
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        if (isset($result['errors'])) {
            $error = implode('<br>', $result['errors']);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-banner">
            <iconify-icon icon="fluent-emoji:soup-pot" style="font-size: 96px;"></iconify-icon>
            <h1>FOODIE</h1>
            <p>Delicious food, delivered fast straight to your doorstep.</p>
        </div>
        <div class="auth-form-panel">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <iconify-icon icon="lucide:alert-circle"></iconify-icon>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <iconify-icon icon="lucide:check-circle"></iconify-icon>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-group">
                <button class="tab-btn active" onclick="switchTab('login')">Login</button>
                <button class="tab-btn" onclick="switchTab('register')">Register</button>
            </div>
            
            <!-- Login Form -->
            <form id="login-form" method="POST" action="">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember" <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>> 
                        Remember me
                    </label>
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" class="btn-primary">Sign In</button>
            </form>
            
            <!-- Register Form -->
            <form id="register-form" method="POST" action="" style="display:none;">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="09123456789">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
            
            <div class="divider"><span>Or continue with</span></div>
            <div class="social-group">
                <button class="social-btn"><iconify-icon icon="logos:google-icon"></iconify-icon> Google</button>
                <button class="social-btn"><iconify-icon icon="logos:apple"></iconify-icon> Apple</button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const tabs = document.querySelectorAll('.tab-btn');
            
            if (tab === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                tabs[1].classList.add('active');
                tabs[0].classList.remove('active');
            }
        }
    </script>
</body>
</html>