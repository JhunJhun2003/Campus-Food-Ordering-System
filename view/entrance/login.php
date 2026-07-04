<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;

// ✅ Check for verification success message from session
$success = $_SESSION['verification_success'] ?? '';
unset($_SESSION['verification_success']);

// Check for other messages
if (empty($success)) {
    $success = $_SESSION['success'] ?? '';
    unset($_SESSION['success']);
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$controller = new UserController();

// Redirect if already logged in
if ($controller->isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/admin-dashboard.php');
    } else {
        header('Location: ../customer/dashboard.php');
    }
    exit();
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
        // Get the user from response
        $user = $result['user'];
        $registeredUserId = $user->getId()->getValue();
        $registeredEmail = $user->getEmail()->getValue();
        
        // Send verification code
        $userRepo = new \App\User\Infrastructure\Repositories\UserRepository();
        $sendVerification = new \App\User\Application\Usecases\SendVerificationUseCase($userRepo);
        $verifyResult = $sendVerification->execute($registeredUserId);
        
        if ($verifyResult['success']) {
            // Store user info in session for verification page
            $_SESSION['user_id'] = $registeredUserId;
            $_SESSION['user_email'] = $registeredEmail;
            $_SESSION['test_code'] = $verifyResult['code'];
            
            // Redirect to verification page
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
?>
<!-- Rest of HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Login & Register</title>
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

        @keyframes steamFloat {
            0% {
                transform: translateY(4px) scaleY(0.85);
                opacity: 0.1;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                transform: translateY(-24px) scaleY(1.15);
                opacity: 0;
            }
        }

        .steam-line {
            animation: steamFloat 3s ease-in-out infinite;
            transform-origin: bottom;
        }
        .steam-delay-1 { animation-delay: 0.4s; }
        .steam-delay-2 { animation-delay: 1.1s; }
        .steam-delay-3 { animation-delay: 1.8s; }
        .steam-delay-4 { animation-delay: 2.3s; }

        .form-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8">

    <!-- Card Frame -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-100/50 w-full max-w-5xl overflow-hidden flex flex-col md:flex-row min-h-[580px] transition-all duration-300">
        
        <!-- LEFT COLUMN: Brand Panel -->
        <div class="w-full md:w-1/2 bg-slate-50/50 p-8 sm:p-12 flex flex-col items-center justify-center border-r border-slate-100/80 relative overflow-hidden">
            
            <div class="flex flex-col items-center justify-center mb-2">
                <div class="relative flex items-center justify-center text-slate-900 mb-2">
                    <span class="fa-stack fa-2xl">
                        <svg viewBox="0 0 100 100" class="w-16 h-16 fill-current text-slate-950">
                            <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                            <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                            <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                    </span>
                </div>
                <span class="text-3xl font-extrabold tracking-wider text-slate-950">FOODIE</span>
                <p class="text-base sm:text-lg font-bold text-slate-900 mt-2 text-center">Delicious Food, Delivered Fast</p>
            </div>

            <!-- Illustrated Pots -->
            <div class="relative w-full max-w-sm aspect-[4/3] mt-6 select-none">
                <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-md">
                    <defs>
                        <path id="steam-wave" d="M 0 0 C 10 -10, -10 -30, 0 -40 C 10 -50, -10 -70, 0 -80" fill="none" stroke="#CBD5E1" stroke-width="3" stroke-linecap="round" />
                    </defs>

                    <!-- Pot 1 -->
                    <g transform="translate(40, 95) scale(0.75)">
                        <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-1" />
                        <use href="#steam-wave" x="140" y="45" class="steam-line steam-delay-3" />
                        <use href="#steam-wave" x="100" y="55" class="steam-line steam-delay-2" />
                        <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                        <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                        <path d="M 35 70 C 35 150, 205 150, 205 70 Z" fill="#334155" />
                        <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                        <polygon points="90,65 105,62 100,74" fill="#F97316" />
                        <polygon points="140,72 155,75 148,64" fill="#F97316" />
                        <path d="M 110,75 Q 115,68 122,76" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                        <path d="M 70,72 Q 74,66 79,73" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" />
                        <circle cx="125" cy="65" r="7" fill="#78350F" />
                        <circle cx="85" cy="73" r="6" fill="#78350F" />
                        <circle cx="155" cy="68" r="8" fill="#78350F" />
                    </g>

                    <!-- Pot 2 -->
                    <g transform="translate(180, 95) scale(0.75)">
                        <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-2" />
                        <use href="#steam-wave" x="140" y="45" class="steam-line steam-delay-4" />
                        <use href="#steam-wave" x="100" y="55" class="steam-line steam-delay-1" />
                        <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                        <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                        <path d="M 35 70 C 35 150, 205 150, 205 70 Z" fill="#334155" />
                        <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                        <polygon points="85,68 95,61 97,71" fill="#F97316" />
                        <polygon points="130,71 145,74 138,63" fill="#F97316" />
                        <path d="M 105,73 Q 110,66 117,74" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                        <path d="M 150,70 Q 155,64 162,71" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" />
                        <circle cx="115" cy="64" r="7" fill="#78350F" />
                        <circle cx="75" cy="72" r="6" fill="#78350F" />
                        <circle cx="145" cy="67" r="8" fill="#78350F" />
                    </g>

                    <!-- Pot 3 (Center Front) -->
                    <g transform="translate(90, 130) scale(0.9)">
                        <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-3" />
                        <use href="#steam-wave" x="145" y="45" class="steam-line steam-delay-1" />
                        <use href="#steam-wave" x="95" y="55" class="steam-line steam-delay-4" />
                        <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#3F4E5F" stroke-width="16" stroke-linecap="round" />
                        <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#3F4E5F" stroke-width="16" stroke-linecap="round" />
                        <path d="M 35 70 C 35 155, 205 155, 205 70 Z" fill="#3F4E5F" />
                        <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                        <polygon points="75,70 90,66 84,78" fill="#F97316" />
                        <polygon points="120,74 135,76 128,66" fill="#F97316" />
                        <polygon points="160,67 172,74 165,62" fill="#F97316" />
                        <path d="M 95,74 Q 102,66 111,75" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                        <path d="M 140,73 Q 146,65 155,72" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                        <circle cx="105" cy="65" r="8" fill="#78350F" />
                        <circle cx="150" cy="68" r="7" fill="#78350F" />
                    </g>
                </svg>
            </div>
        </div>

        <!-- RIGHT COLUMN: Login & Register Forms -->
        <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-between">
            <div>
                <!-- Alert Messages -->
                <?php if ($error): ?>
                    <div class="alert-error px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-success px-4 py-3 rounded-xl mb-4 text-sm font-medium flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Top Tabs -->
                <div class="flex bg-slate-100 p-1.5 rounded-xl mb-8 w-full max-w-sm mx-auto">
                    <button id="tab-login" onclick="switchTab('login')" class="flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 bg-emerald-500 text-white shadow-sm">
                        Login
                    </button>
                    <button id="tab-register" onclick="switchTab('register')" class="flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 text-slate-500 hover:text-slate-900">
                        Register
                    </button>
                </div>

                <!-- FORM -->
                <form method="POST" action="" class="space-y-5 max-w-sm mx-auto" id="auth-form">
                    
                    <!-- Register Fields -->
                    <div id="register-fields" class="hidden space-y-5 form-transition opacity-0 max-h-0 overflow-hidden">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1.5">Full Name</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-regular fa-user"></i>
                                </span>
                                <input name="name" type="text" placeholder="Enter full name" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label id="identity-label" class="block text-sm font-semibold text-slate-800 mb-1.5">Email / Username</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input id="identity-input" name="email" type="text" required placeholder="Enter email or username" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="text-sm font-semibold text-slate-800">Password</label>
                            <a href="#" id="forgot-password" onclick="triggerForgotPassword(event)" class="text-xs font-semibold text-slate-500 hover:text-emerald-600 transition-colors">Forgot Password?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input id="password-input" name="password" type="password" required placeholder="Enter Password" class="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <i id="password-toggle-icon" class="fa-regular fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Phone (Register only) -->
                    <div id="register-phone" class="hidden space-y-5 form-transition opacity-0 max-h-0 overflow-hidden">
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-1.5">Phone Number</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <input name="phone" type="tel" placeholder="Enter phone number" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button id="submit-btn" type="submit" name="login" value="1" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 text-sm tracking-wide">
                        Login
                    </button>
                </form>

                <!-- Divider -->
                <div class="my-6 flex items-center justify-center max-w-sm mx-auto">
                    <div class="flex-grow border-t border-slate-200/80"></div>
                    <span class="px-3 text-xs text-slate-400 font-semibold uppercase tracking-wider">Or</span>
                    <div class="flex-grow border-t border-slate-200/80"></div>
                </div>

                <!-- Social Buttons -->
                <div class="space-y-3 max-w-sm mx-auto">
                    <button onclick="handleSocialAuth('Google')" class="w-full flex items-center justify-center space-x-3 px-4 py-2.5 border border-slate-200 rounded-xl bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path fill="#EA4335" d="M12 5.04c1.62 0 3.08.56 4.22 1.64l3.15-3.15C17.45 1.74 14.93 1 12 1 7.35 1 3.39 3.65 1.5 7.5l3.86 3C6.27 7.42 8.91 5.04 12 5.04z"/>
                            <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.48-1.12 2.73-2.38 3.58l3.69 2.87c2.16-1.99 3.74-4.92 3.74-8.55z"/>
                            <path fill="#FBBC05" d="M5.36 14.5c-.24-.72-.38-1.49-.38-2.5s.14-1.78.38-2.5L1.5 6.5C.54 8.42 0 10.58 0 13s.54 4.58 1.5 6.5l3.86-3z"/>
                            <path fill="#34A853" d="M12 23c3.24 0 5.97-1.08 7.96-2.91l-3.69-2.87c-1.02.68-2.33 1.09-4.27 1.09-3.09 0-5.73-2.38-6.66-5.46l-3.86 3C3.39 20.35 7.35 23 12 23z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </button>

                    <button onclick="handleSocialAuth('Apple')" class="w-full flex items-center justify-center space-x-3 px-4 py-2.5 bg-black text-white rounded-xl text-sm font-semibold hover:bg-slate-900 transition-all shadow-sm">
                        <i class="fa-brands fa-apple text-base"></i>
                        <span>Continue with Apple</span>
                    </button>
                </div>
            </div>

            <!-- Bottom Redirect -->
            <div class="mt-8 text-center">
                <p id="bottom-hint" class="text-xs text-slate-500 font-medium">
                    Don't have an account? 
                    <a href="#" onclick="switchTab('register'); event.preventDefault();" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Register</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-notification" class="fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-xl flex items-center space-x-3 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50">
        <div id="toast-icon" class="text-emerald-400">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <p id="toast-message" class="text-sm font-semibold"></p>
        </div>
    </div>

    <script>
        let currentMode = 'login';

        function switchTab(mode) {
            if (currentMode === mode) return;
            currentMode = mode;

            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');
            const submitBtn = document.getElementById('submit-btn');
            const registerFields = document.getElementById('register-fields');
            const registerPhone = document.getElementById('register-phone');
            const identityLabel = document.getElementById('identity-label');
            const identityInput = document.getElementById('identity-input');
            const bottomHint = document.getElementById('bottom-hint');
            const forgotPassword = document.getElementById('forgot-password');

            if (mode === 'login') {
                // Update tabs
                tabLogin.className = "flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 bg-emerald-500 text-white shadow-sm";
                tabRegister.className = "flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 text-slate-500 hover:text-slate-900";

                // Update form
                submitBtn.innerText = "Login";
                submitBtn.name = "login";
                submitBtn.value = "1";
                identityLabel.innerText = "Email / Username";
                identityInput.placeholder = "Enter email or username";
                forgotPassword.style.display = "block";

                // Hide register fields
                registerFields.classList.add('opacity-0', 'max-h-0', 'overflow-hidden');
                registerFields.classList.remove('opacity-100', 'max-h-40');
                setTimeout(() => registerFields.classList.add('hidden'), 300);
                
                registerPhone.classList.add('opacity-0', 'max-h-0', 'overflow-hidden');
                registerPhone.classList.remove('opacity-100', 'max-h-40');
                setTimeout(() => registerPhone.classList.add('hidden'), 300);

                // Update bottom hint
                bottomHint.innerHTML = 'Don\'t have an account? <a href="#" onclick="switchTab(\'register\'); event.preventDefault();" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Register</a>';
            } else {
                // Update tabs
                tabRegister.className = "flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 bg-emerald-500 text-white shadow-sm";
                tabLogin.className = "flex-1 py-2.5 text-center text-sm font-semibold rounded-lg transition-all duration-250 text-slate-500 hover:text-slate-900";

                // Update form
                submitBtn.innerText = "Register";
                submitBtn.name = "register";
                submitBtn.value = "1";
                identityLabel.innerText = "Email Address";
                identityInput.placeholder = "Enter email address";
                forgotPassword.style.display = "none";

                // Show register fields
                registerFields.classList.remove('hidden');
                registerPhone.classList.remove('hidden');
                setTimeout(() => {
                    registerFields.classList.remove('opacity-0', 'max-h-0', 'overflow-hidden');
                    registerFields.classList.add('opacity-100', 'max-h-40');
                    registerPhone.classList.remove('opacity-0', 'max-h-0', 'overflow-hidden');
                    registerPhone.classList.add('opacity-100', 'max-h-40');
                }, 50);

                // Update bottom hint
                bottomHint.innerHTML = 'Already have an account? <a href="#" onclick="switchTab(\'login\'); event.preventDefault();" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Login</a>';
            }
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password-input');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fa-regular fa-eye';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fa-regular fa-eye-slash';
            }
        }

        function showNotification(message, isSuccess = true) {
            const toast = document.getElementById('toast-notification');
            const messageEl = document.getElementById('toast-message');
            const iconEl = document.getElementById('toast-icon');

            messageEl.innerText = message;
            if (isSuccess) {
                iconEl.innerHTML = '<i class="fa-solid fa-circle-check text-lg text-emerald-400"></i>';
            } else {
                iconEl.innerHTML = '<i class="fa-solid fa-circle-xmark text-lg text-rose-500"></i>';
            }

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 4000);
        }

        function handleSocialAuth(provider) {
            showNotification(`Sign-in procedure initialized via ${provider}.`);
        }

        function triggerForgotPassword(event) {
            event.preventDefault();
            const identity = document.getElementById('identity-input').value;
            if (!identity) {
                showNotification("Please enter your email or username above.", false);
            } else {
                showNotification(`Password reset link sent to: ${identity}`);
            }
        }
    </script>
</body>
</html>