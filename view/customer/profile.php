<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;
use App\Cart\Presentation\Http\Controllers\CartController;

$userController = new UserController();

// Check if user is logged in
if (!$userController->isLoggedIn()) {
    header('Location: ../entrance/login.php');
    exit();
}

$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'];

// Get cart item count
$itemCount = 0;
try {
    $cartController = new CartController();
    $itemCount = $cartController->getItemCount($userId);
} catch (\Exception $e) {
    $itemCount = 0;
}

// Get user profile
$profile = $userController->getProfile($userId);

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }

    $result = $userController->updateProfile($userId, $data);

    if ($result['success']) {
        $success = $result['message'];
        // Refresh profile data
        $profile = $userController->getProfile($userId);
        // Update session
        $_SESSION['user_name'] = $data['name'];
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #FCFDFE;
        }
        .interactive-transition {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .profile-card {
            animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
            background: white;
            color: #1E293B;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10B981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #94A3B8;
        }
        .form-group .helper-text {
            font-size: 12px;
            color: #94A3B8;
            margin-top: 4px;
        }
        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #10B981, #059669);
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #10B981;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-950">
                    <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 interactive-transition">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
            </a>

            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Orders</a>
            </nav>

            <div class="flex items-center space-x-6">
                <a href="cart.php" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                    <span id="header-cart-badge" class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center border-2 border-white shadow-sm <?php echo $itemCount > 0 ? '' : 'hidden'; ?>">
                        <?php echo $itemCount; ?>
                    </span>
                </a>
                <a href="profile.php" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-regular fa-user text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">My Profile</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your personal information and preferences</p>
        </div>

        <!-- Profile Card -->
        <div class="profile-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Avatar & Basic Info -->
            <div class="bg-gradient-to-r from-emerald-50 to-white p-6 border-b border-slate-100">
                <div class="flex items-center gap-6">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($profile['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-900"><?php echo htmlspecialchars($profile['name'] ?? 'User'); ?></h2>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></p>
                        <p class="text-xs text-slate-400 mt-1">Member since <?php echo date('F j, Y', strtotime($profile['created_at'] ?? 'now')); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="p-6">
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

                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label>Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="09123456789">
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label>Delivery Address</label>
                        <textarea name="address" rows="2" placeholder="Enter your delivery address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Leave blank to keep current password">
                        <div class="helper-text">Must be at least 8 characters with uppercase, lowercase, and a number</div>
                    </div>

                    <button type="submit" class="btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="orders.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4">
                <i class="fa-regular fa-receipt text-2xl text-emerald-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">My Orders</p>
                    <p class="text-xs text-slate-400">View order history</p>
                </div>
            </a>
            <a href="cart.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4">
                <i class="fa-solid fa-cart-shopping text-2xl text-emerald-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">My Cart</p>
                    <p class="text-xs text-slate-400">View your cart</p>
                </div>
            </a>
            <a href="../entrance/logout.php" class="bg-white border border-slate-100 rounded-xl p-4 hover:shadow-md interactive-transition flex items-center gap-4 hover:border-red-200">
                <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
                <div>
                    <p class="text-sm font-bold text-slate-900">Logout</p>
                    <p class="text-xs text-slate-400">Sign out of your account</p>
                </div>
            </a>
        </div>

    </main>

    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; <?php echo date('Y'); ?> FOODIE INC. All rights reserved. Delicious Food, Delivered Fast.
        </div>
    </footer>

</body>
</html>