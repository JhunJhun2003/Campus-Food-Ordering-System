<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php'; // ✅ Added

use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Infrastructure\Repositories\UserRepository;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\UserId;

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// ✅ Use helper - NO 'new' keyword!
$userController = getUserController();

// If user is already logged in (verified), redirect to dashboard
if ($userController->isLoggedIn() && $userController->isVerified()) {
    $role = $_SESSION['user_role'] ?? 'user';
    $redirectMap = [
        'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
        'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
        'user' => '/Campus-Food-Ordering-System/view/customer/dashboard.php',
    ];
    header('Location: ' . ($redirectMap[$role] ?? $redirectMap['user']));
    exit();
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
                    $_SESSION['user_email'] = $email;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
    
    // Handle Verify Code
    if (isset($_POST['verify_email'])) {
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
                $success = 'Email verified successfully!';
                $isVerified = true;

                if (isset($_SESSION['user_role'])) {
                    $_SESSION['user_verified'] = true;
                } else {
                    // Clear session data from registration if not logged in
                    unset($_SESSION['user_id']);
                    unset($_SESSION['user_email']);
                }
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
$customCss = 'css/login.css';
$simpleHeader = true;

include __DIR__ . '/includes/header.php';
?>

<div class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
    <div class="auth-card">
        <?php 
            $brandTitle = 'FOODIE';
            $brandSubtitle = 'Verify your email address';
            include __DIR__ . '/includes/brand-panel.php';
        ?>

        <div class="form-panel">
            <div>
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
                    <div class="text-center mt-6">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check text-2xl text-emerald-500"></i>
                        </div>
                        <p class="text-sm text-slate-600 mb-4">Your email has been verified successfully!</p>
                        <?php if (isset($_SESSION['user_role'])): ?>
                        <a href="/Campus-Food-Ordering-System/view/customer/dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20">
                            <span>Go to Dashboard</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20">
                            <span>Go to Login</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-8 text-center">
                        <div class="w-14 h-14 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-regular fa-envelope text-2xl text-indigo-500"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900">Verify Email</h2>
                        <p class="text-sm text-slate-500 mt-1">Enter the 4-digit code sent to your email</p>
                    </div>

                    <div class="email-display mb-8 text-center">
                        <p class="text-sm text-slate-600">
                            We sent a code to<br>
                            <strong class="text-slate-900 text-base"><?php echo htmlspecialchars($userEmail); ?></strong>
                        </p>
                        <p class="text-xs text-slate-400 mt-2">
                            <i class="fa-regular fa-clock mr-1"></i> Please check your inbox and spam folder
                        </p>
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="auth-form">
                        <div class="form-group">
                            <label class="form-label text-center block">Verification Code</label>
                            <input type="text" 
                                   name="verification_code" 
                                   maxlength="4" 
                                   placeholder="— — — —" 
                                   required 
                                   class="code-input w-full text-center text-3xl sm:text-4xl font-bold tracking-[12px] sm:tracking-[16px] py-4 px-4 bg-white border-2 border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                                   autofocus>
                            <p class="text-xs text-slate-400 text-center mt-2">Enter the 4-digit code sent to your email</p>
                        </div>

                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                        <input type="hidden" name="verify_email" value="1">

                        <button type="submit" name="verify_email" value="1" class="btn-submit w-full py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 text-sm tracking-wide">
                            Verify Email
                        </button>
                    </form>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mt-4">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
                        <button type="submit" name="resend_code" value="1" class="resend-btn text-sm font-semibold text-emerald-500 hover:text-emerald-600 transition-colors bg-transparent border-none cursor-pointer">
                            Didn't receive the code? Resend
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- <div class="bottom-hint mt-8 text-center">
                <a href="login.php" class="text-sm text-slate-500 hover:text-emerald-600 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back to Login
                </a>
            </div> -->
        </div>
    </div>
</div>

<style>
.code-input {
    letter-spacing: 12px;
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    width: 100%;
    padding: 16px 8px;
    background: white;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    transition: all 0.2s ease;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.code-input:focus {
    outline: none;
    border-color: #10B981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);
}

.code-input::placeholder {
    color: #94A3B8;
    letter-spacing: 4px;
    font-weight: 300;
}

@media (max-width: 640px) {
    .code-input {
        font-size: 24px;
        letter-spacing: 8px;
        padding: 12px 4px;
    }
}

.email-display strong {
    word-break: break-all;
}

.resend-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #10B981;
    transition: color 0.2s ease;
}

.resend-btn:hover {
    color: #059669;
}

.alert-success {
    background-color: #D1FAE5;
    border: 1px solid #6EE7B7;
    color: #065F46;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-error {
    background-color: #FEE2E2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

<script>
    const codeInput = document.querySelector('input[name="verification_code"]');
    if (codeInput) {
        // Only allow digits
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
        
        // Auto-submit when all 4 digits are entered
        codeInput.addEventListener('keyup', function() {
            if (this.value.length === 4) {
                this.form.submit();
            }
        });
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>