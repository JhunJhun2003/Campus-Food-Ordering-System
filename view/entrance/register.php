<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;

$controller = new UserController();

// Redirect if already logged in
if ($controller->isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/admin-dashboard.php');
    } else {
        header('Location: ../customer/menu.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->register();
    
    if ($result['success']) {
        $success = $result['message'];
        // Optional: Auto-login after registration
        // header('Location: login.php');
        // exit();
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
    <title>Foodie - Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="login.css">
    <style>
        /* Additional register page specific styles */
        .auth-form-panel {
            padding: 40px 35px;
        }
        .register-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .register-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .register-footer a:hover {
            text-decoration: underline;
        }
        .form-group .helper-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-banner">
            <iconify-icon icon="fluent-emoji:soup-pot" style="font-size: 96px;"></iconify-icon>
            <h1>FOODIE</h1>
            <p>Create your account and start ordering delicious food!</p>
        </div>
        <div class="auth-form-panel">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Join Foodie and enjoy the best campus food delivery</p>
            </div>

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
                    <br>
                    <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Click here to login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="09123456789" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <div class="helper-text">Optional but recommended for delivery updates</div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                        <div class="helper-text">Must be at least 8 characters with uppercase, lowercase, and a number</div>
                    </div>
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>
                
                <div class="divider"><span>Already have an account?</span></div>
                <div class="register-footer">
                    <a href="login.php">Sign in here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>