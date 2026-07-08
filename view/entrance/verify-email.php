<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Infrastructure\Repositories\UserRepository;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\UserId;

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// Do NOT auto-login the user. Only store email for verification purposes.
// If user is already logged in, redirect to dashboard (they don't need verification)
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    // Check if already verified
    $userRepo = new UserRepository();
    $user = $userRepo->findById(new UserId((int) $_SESSION['user_id']));
    if ($user && $user->isVerified()) {
        // Already verified, redirect to dashboard
        $role = $_SESSION['user_role'] ?? 'user';
        $redirectMap = [
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            'user' => '/Campus-Food-Ordering-System/view/customer/dashboard.php',
        ];
        header('Location: ' . ($redirectMap[$role] ?? $redirectMap['user']));
        exit();
    }
}

// Get email from session or POST
$userEmail = $_SESSION['user_email'] ?? '';

// If no email in session, try to get from POST
if (empty($userEmail) && isset($_POST['email'])) {
    $userEmail = trim($_POST['email']);
}

// If still no email, redirect to login
if (empty($userEmail)) {
    $_SESSION['error'] = 'Please register first or login to verify your email.';
    header('Location: login.php');
    exit();
}

// ============================================
// 2. BUSINESS LOGIC - HANDLE REQUESTS
// ============================================

$error = getErrorMessage();
$success = getSuccessMessage();
$isVerified = false;

// Handle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Resend Code
    if (isset($_POST['resend_code'])) {
        $email = trim($_POST['email'] ?? $userEmail);
        if (empty($email)) {
            $error = 'Email address is required.';
        } else {
            $verificationRepo = new EmailVerificationRepository();
            $userRepo = new UserRepository();
            $user = $userRepo->findByEmail(new Email($email));
            
            if (!$user) {
                $error = 'User not found with this email.';
            } else {
                $userId = $user->getId()->getValue();
                $sendVerification = new \App\User\Application\Usecases\SendVerificationUseCase($userRepo, $verificationRepo);
                $result = $sendVerification->execute($userId);
                
                if ($result['success']) {
                    $success = 'New verification code sent to your email!';
                    $_SESSION['test_code'] = $result['code'];
                    $_SESSION['user_email'] = $email;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
    
    // Handle Verify Code
    if (!isset($_POST['resend_code']) && (!empty($_POST['verification_code']) || isset($_POST['verify_email']))) {
        $email = trim($_POST['email'] ?? $userEmail);
        $code = trim($_POST['verification_code'] ?? '');
        
        if (empty($email) || empty($code)) {
            $error = 'Please enter your email and verification code.';
        } else {
            $verificationRepo = new EmailVerificationRepository();
            $userRepo = new UserRepository();
            $verifyEmail = new \App\User\Application\Usecases\VerifyEmailUseCase($userRepo, $verificationRepo);
            $result = $verifyEmail->execute($email, $code);
            
            if ($result['success']) {
                $success = 'Email verified successfully! You can now login.';
                $isVerified = true;
                
                // Clear any session data from registration
                unset($_SESSION['user_id']);
                unset($_SESSION['user_email']);
                unset($_SESSION['test_code']);
            } else {
                $error = $result['message'];
            }
        }
    }
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Verify Email';
$customCss = 'css/verify-email.css';
$simpleHeader = true;
include __DIR__ . '/includes/header.php';


?>

<div class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
    <div class="verify-card">
        
        <!-- Header -->
        <div class="verify-header">
            <svg viewBox="0 0 100 100" class="logo fill-current text-slate-950">
                <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
            </svg>
            <h1>FOODIE</h1>
            <p>Verify your email address</p>
        </div>

        <!-- Body -->
        <div class="verify-body">
            
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success && $isVerified): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <div class="text-center mt-4">
                    <p class="text-sm text-slate-500 mb-4">You can now login to your account.</p>
                    <a href="login.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20">
                        <span>Go to Login</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                
            <?php else: ?>
                
                <div class="email-display">
                    <p>
                        We sent a 4-digit code to<br>
                        <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                    </p>
                    <p class="hint">Please check your inbox and spam folder.</p>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="form-label">Verification Code</label>
                        <input type="text" 
                               name="verification_code" 
                               maxlength="4" 
                               placeholder="• • • •" 
                               required 
                               class="code-input"
                               autofocus>
                        <p class="code-hint">Enter the 4-digit code sent to your email</p>
                    </div>

                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                    <input type="hidden" name="verify_email" value="1">

                    <button type="submit" name="verify_email" class="btn-verify">Verify Email</button>
                    
                    <div class="resend-text">
                        Didn't receive the code? 
                        <button type="submit" name="resend_code" value="1" class="resend-btn">Resend</button>
                    </div>
                </form>

            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="verify-footer">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</div>

<script>
    const codeInput = document.querySelector('input[name="verification_code"]');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
        
        codeInput.addEventListener('keyup', function() {
            if (this.value.length === 4) {
                this.form.submit();
            }
        });
    }

    <?php if (isset($_SESSION['test_code'])): ?>
        const testCode = <?php echo json_encode($_SESSION['test_code']); ?>;
        if (testCode && codeInput) {
            codeInput.value = testCode;
            setTimeout(() => {
                if (codeInput.value.length === 4) {
                    codeInput.form.submit();
                }
            }, 1000);
        }
    <?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>