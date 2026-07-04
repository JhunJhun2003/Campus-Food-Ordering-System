<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Infrastructure\Repositories\UserRepository;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

// Get user email from database if not in session
if (empty($userEmail)) {
    $userRepo = new UserRepository();
    $user = $userRepo->findById(new \App\User\Domain\ValueObjects\UserId($userId));
    if ($user) {
        $userEmail = $user->getEmail()->getValue();
        $_SESSION['user_email'] = $userEmail;
    }
}

$error = '';
$success = '';
$isVerified = false;

// Handle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_email'])) {
    $email = trim($_POST['email'] ?? '');
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
            // Clear session and redirect to login
            echo '<meta http-equiv="refresh" content="3;url=login.php">';
        } else {
            $error = $result['message'];
        }
    }
}

// Resend verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email address is required.';
    } else {
        $verificationRepo = new EmailVerificationRepository();
        $userRepo = new UserRepository();
        $sendVerification = new \App\User\Application\Usecases\SendVerificationUseCase($userRepo, $verificationRepo);
        $result = $sendVerification->execute($userId);
        
        if ($result['success']) {
            $success = 'New verification code sent to your email!';
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
    <title>Foodie - Verify Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
        }
        .alert-error {
            background-color: #FEE2E2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
        }
        .alert-success {
            background-color: #D1FAE5;
            border: 1px solid #6EE7B7;
            color: #065F46;
        }
        .verification-code-input {
            letter-spacing: 12px;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
        }
        .resend-btn {
            color: #10B981;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s;
        }
        .resend-btn:hover {
            color: #059669;
        }
        .code-hint {
            color: #94A3B8;
            font-size: 13px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8">

    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-100/50 w-full max-w-md overflow-hidden transition-all duration-300">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-50 to-white p-6 border-b border-slate-100 text-center">
            <div class="flex items-center justify-center mb-3">
                <span class="fa-stack fa-2xl">
                    <svg viewBox="0 0 100 100" class="w-12 h-12 fill-current text-slate-950">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-950">FOODIE</h1>
            <p class="text-sm text-slate-500 mt-1">Verify your email address</p>
        </div>

        <!-- Body -->
        <div class="p-6">
            
            <?php if ($error): ?>
                <div class="alert-error px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success && $isVerified): ?>
                <div class="alert-success px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <div class="text-center mt-4">
                    <p class="text-sm text-slate-500 mb-4">Redirecting to login page...</p>
                    <a href="login.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20">
                        <span>Login Now</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                
                <div class="mb-4 text-center">
                    <p class="text-sm text-slate-600">
                        We sent a 4-digit code to<br>
                        <strong class="text-slate-900"><?php echo htmlspecialchars($userEmail); ?></strong>
                    </p>
                    <p class="text-xs text-slate-400 mt-2">Please check your inbox and spam folder.</p>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">Verification Code</label>
                        <input type="text" 
                               name="verification_code" 
                               maxlength="4" 
                               placeholder="• • • •" 
                               required 
                               class="verification-code-input w-full px-4 py-4 bg-white border-2 border-slate-200 rounded-xl text-sm placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                               autofocus>
                        <p class="code-hint mt-2 text-center">Enter the 4-digit code sent to your email</p>
                    </div>

                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">

                    <button type="submit" name="verify_email" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 text-sm tracking-wide">
                        Verify Email
                    </button>
                    
                    <div class="text-center text-sm text-slate-500">
                        Didn't receive the code? 
                        <button type="submit" name="resend_code" value="1" class="resend-btn bg-transparent border-none cursor-pointer font-semibold">
                            Resend
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="px-6 pb-6 text-center">
            <p class="text-xs text-slate-400">
                <a href="login.php" class="text-slate-500 hover:text-emerald-600 font-medium">← Back to Login</a>
            </p>
        </div>
    </div>

    <script>
        // Auto-format verification code input
        const codeInput = document.querySelector('input[name="verification_code"]');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
            
            // Auto-submit when 4 digits are entered
            codeInput.addEventListener('keyup', function() {
                if (this.value.length === 4) {
                    this.form.submit();
                }
            });
        }
    </script>

</body>
</html>