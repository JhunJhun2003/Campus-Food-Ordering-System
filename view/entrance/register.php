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
        header('Location: ../customer/dashboard.php');
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
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8">

    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-100/50 w-full max-w-5xl overflow-hidden flex flex-col md:flex-row min-h-[580px] transition-all duration-300">
        
        <!-- LEFT COLUMN -->
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
                <p class="text-base sm:text-lg font-bold text-slate-900 mt-2 text-center">Create Your Account</p>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center">
            
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
                <div class="text-center mt-2">
                    <a href="login.php" class="text-emerald-600 hover:text-emerald-700 font-semibold text-sm">Click here to login →</a>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Register</h2>
                    <p class="text-sm text-slate-500">Create your account to start ordering</p>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">Full Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input name="name" type="text" placeholder="Enter full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input name="email" type="email" placeholder="Enter email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">Phone Number</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <input name="phone" type="tel" placeholder="Enter phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Optional but recommended for delivery updates</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <input name="password" type="password" placeholder="Enter password" required class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters with uppercase, lowercase, and a number</p>
                    </div>

                    <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-emerald-500/10 hover:shadow-emerald-500/20 text-sm tracking-wide">
                        Create Account
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-xs text-slate-500 font-medium">
                        Already have an account? 
                        <a href="login.php" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Login</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>