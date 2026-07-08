<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\User\Presentation\Http\Controllers\UserController;

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// Redirect if already logged in
redirectIfLoggedIn();

// ============================================
// 2. BUSINESS LOGIC - HANDLE REQUESTS
// ============================================

$controller = new UserController();
$error = getErrorMessage();
$success = getVerificationSuccess();

if (empty($success)) {
    $success = getSuccessMessage();
}

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
        $user = $result['user'];
        $registeredUserId = $user->getId()->getValue();
        $registeredEmail = $user->getEmail()->getValue();
        
        // Send verification code
        $userRepo = new \App\User\Infrastructure\Repositories\UserRepository();
        $sendVerification = new \App\User\Application\Usecases\SendVerificationUseCase($userRepo);
        $verifyResult = $sendVerification->execute($registeredUserId);
        
        if ($verifyResult['success']) {
            $_SESSION['user_id'] = $registeredUserId;
            $_SESSION['user_email'] = $registeredEmail;
            $_SESSION['test_code'] = $verifyResult['code'];
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

$pageTitle = 'Foodie - Login & Register';
$customCss = 'css/login.css';
$simpleHeader = true;

include __DIR__ . '/includes/header.php';
?>

<!-- Card Frame -->
<div class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
    <div class="auth-card">
        
        <!-- Left Column: Brand Panel -->
        <?php 
        $brandTitle = 'FOODIE';
        $brandSubtitle = 'Delicious Food, Delivered Fast';
        include __DIR__ . '/includes/brand-panel.php'; 
        ?>

        <!-- Right Column: Form -->
        <div class="form-panel">
            <div>
                <!-- Alert Messages -->
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

                <!-- Top Tabs -->
                <!-- <div class="auth-tabs">
                    <button id="tab-login" onclick="switchTab('login')" class="auth-tab active">Login</button>
                    <button id="tab-register" onclick="switchTab('register')" class="auth-tab">Register</button>
                </div> -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Login</h2>
                    <p class="text-sm text-slate-500">Login your account to start ordering</p>
                </div>

                <!-- FORM -->
                <form method="POST" action="" class="auth-form" id="auth-form">
                    
                    <!-- Register Fields -->
                    <div id="register-fields" class="register-fields">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-regular fa-user"></i>
                                </span>
                                <input name="name" type="text" placeholder="Enter full name" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label id="identity-label" class="form-label">Email / Username</label>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input id="identity-input" name="email" type="text" required placeholder="Enter email or username" class="form-input">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group password-field">
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="form-label mb-0">Password</label>
                            <a href="#" id="forgot-password" onclick="triggerForgotPassword(event)" class="forgot-link">Forgot Password?</a>
                        </div>
                        <div class="form-input-wrapper">
                            <span class="form-input-icon">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="password-input" name="password" type="password" required placeholder="Enter Password" class="form-input form-input-password">
                            <button type="button" onclick="togglePasswordVisibility()" class="password-toggle">
                                <i id="password-toggle-icon" class="fa-regular fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Phone (Register only) -->
                    <div id="register-phone" class="register-fields">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input name="phone" type="tel" placeholder="Enter phone number" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button id="submit-btn" type="submit" name="login" value="1" class="btn-submit">Login</button>
                </form>

                <!-- Divider -->
                <div class="auth-divider">
                    <span>Or</span>
                </div>

                <!-- Social Buttons -->
                <div class="space-y-3 max-w-sm mx-auto">
                    <button onclick="handleSocialAuth('Google')" class="social-btn">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path fill="#EA4335" d="M12 5.04c1.62 0 3.08.56 4.22 1.64l3.15-3.15C17.45 1.74 14.93 1 12 1 7.35 1 3.39 3.65 1.5 7.5l3.86 3C6.27 7.42 8.91 5.04 12 5.04z"/>
                            <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.48-1.12 2.73-2.38 3.58l3.69 2.87c2.16-1.99 3.74-4.92 3.74-8.55z"/>
                            <path fill="#FBBC05" d="M5.36 14.5c-.24-.72-.38-1.49-.38-2.5s.14-1.78.38-2.5L1.5 6.5C.54 8.42 0 10.58 0 13s.54 4.58 1.5 6.5l3.86-3z"/>
                            <path fill="#34A853" d="M12 23c3.24 0 5.97-1.08 7.96-2.91l-3.69-2.87c-1.02.68-2.33 1.09-4.27 1.09-3.09 0-5.73-2.38-6.66-5.46l-3.86 3C3.39 20.35 7.35 23 12 23z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </button>

                    <!-- <button onclick="handleSocialAuth('Apple')" class="social-btn social-btn-apple">
                        <i class="fa-brands fa-apple text-base"></i>
                        <span>Continue with Apple</span>
                    </button> -->
                </div>
            </div>

            <!-- Bottom Redirect -->
            <div class="bottom-hint" id="bottom-hint">
                Don't have an account? 
                <a href="#" onclick="switchTab('register'); event.preventDefault();">Register</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>