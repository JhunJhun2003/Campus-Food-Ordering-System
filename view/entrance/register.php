<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php';  // ✅ Add this

use App\User\Presentation\Http\Controllers\UserController;
use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Infrastructure\Repositories\UserRepository;

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// Redirect if already logged in
redirectIfLoggedIn();

// ============================================
// 2. BUSINESS LOGIC - HANDLE REQUESTS
// ============================================

// ✅ Use helper - NO 'new' keyword!
$controller = getUserController();
$error = getErrorMessage();
$success = getSuccessMessage();

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $result = $controller->register();
    
    if ($result['success']) {
        $user = $result['user'];
        $registeredUserId = $user->getId()->getValue();
        $registeredEmail = $user->getEmail()->getValue();
        
        // Send verification code
        $verificationRepo = new EmailVerificationRepository();
        $userRepo = new UserRepository();
        $sendVerification = new SendVerificationUseCase($userRepo, $verificationRepo);
        $verifyResult = $sendVerification->execute($registeredUserId);
        
        if ($verifyResult['success']) {
            // ✅ Store only email, NOT user_id (prevents auto-login)
            $_SESSION['user_email'] = $registeredEmail;
            $_SESSION['test_code'] = $verifyResult['code']; // For development auto-fill
            setVerificationSuccess('Registration successful! Please verify your email.');
            header('Location: verify-email.php');
            exit();
        } else {
            $error = $verifyResult['message'];
        }
    } else {
        if (isset($result['errors'])) {
            $error = implode('<br>', $result['errors']);
        } else {
            $error = $result['message'];
        }
    }
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Register';
$customCss = 'css/login.css';
$simpleHeader = true;

include __DIR__ . '/includes/header.php';
?>

<!-- Card Frame - Using same auth-card class as login -->
<div class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
    <div class="auth-card">
        
        <!-- Left Column: Brand Panel -->
        <?php 
        $brandTitle = 'FOODIE';
        $brandSubtitle = 'Create Your Account';
        include __DIR__ . '/includes/brand-panel.php'; 
        ?>

        <!-- Right Column: Form -->
        <div class="form-panel">
            <div>
                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Register</h2>
                    <p class="text-sm text-slate-500">Create your account to start ordering</p>
                </div>

                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input name="name" type="text" placeholder="Enter full name" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input name="email" type="email" placeholder="Enter email address" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input name="password" type="password" placeholder="Enter password" class="form-input" required>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters with uppercase, lowercase, and a number</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input name="phone" type="tel" placeholder="Enter phone number" class="form-input">
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn-submit">Create Account</button>
                </form>

                <div class="bottom-hint">
                    Already have an account? 
                    <a href="login.php">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>