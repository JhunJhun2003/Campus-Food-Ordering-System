<?php
/**
 * Customer Page Header
 * 
 * @var string $pageTitle - Page title
 * @var string $customCss - Custom CSS file path
 * @var string $activePage - Current page for navigation highlighting
 */

$pageTitle = $pageTitle ?? 'Foodie - Customer Panel';
$customCss = $customCss ?? 'css/dashboard.css';
$activePage = $activePage ?? 'dashboard';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user data for the header
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/auth_helper.php';

use App\User\Presentation\Http\Controllers\UserController;

$userController = new UserController();
$isLoggedIn = $userController->isLoggedIn();
$currentUser = null;
$itemCount = 0;

if ($isLoggedIn) {
    $currentUser = $userController->getCurrentUser();
    $userId = $currentUser['id'] ?? null;
    if ($userId) {
        try {
            $cartController = new \App\Cart\Presentation\Http\Controllers\CartController();
            $itemCount = $cartController->getItemCount($userId);
        } catch (\Exception $e) {
            $itemCount = 0;
        }
    }
}

$canViewMenu = !$isLoggedIn || userHasPermission('view_menu');
$canAddToCart = userHasPermission('add_to_cart');
$canViewOrders = userHasPermission('view_orders');
$canUpdateProfile = userHasPermission('update_profile');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($customCss); ?>">
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- ===== HEADER ===== -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40 shadow-sm shadow-slate-100/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Brand Logo -->
            <a href="/Campus-Food-Ordering-System/" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-950">
                    <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 interactive-transition">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center space-x-10">
                <?php if ($canViewMenu): ?>
                <a href="/Campus-Food-Ordering-System/view/customer/dashboard.php" class="text-sm font-bold <?php echo $activePage === 'dashboard' ? 'text-emerald-500 border-b-2 border-emerald-500 pb-1.5' : 'text-slate-600 hover:text-emerald-500'; ?> interactive-transition">
                    Home
                </a>
                <?php endif; ?>
                <?php if ($canAddToCart): ?>
                <a href="/Campus-Food-Ordering-System/view/customer/cart.php" class="text-sm font-semibold <?php echo $activePage === 'cart' ? 'text-emerald-500 border-b-2 border-emerald-500 pb-1.5' : 'text-slate-600 hover:text-emerald-500'; ?> interactive-transition">
                    Cart
                </a>
                <?php endif; ?>
                <?php if ($canViewOrders): ?>
                <a href="/Campus-Food-Ordering-System/view/customer/orders.php" class="text-sm font-semibold <?php echo $activePage === 'orders' ? 'text-emerald-500 border-b-2 border-emerald-500 pb-1.5' : 'text-slate-600 hover:text-emerald-500'; ?> interactive-transition">
                    Orders
                </a>
                <?php endif; ?>
            </nav>

            <!-- Right Interfaces -->
            <div class="flex items-center space-x-6">
                <?php if ($canAddToCart): ?>
                <a href="/Campus-Food-Ordering-System/view/customer/cart.php" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                    <span id="header-cart-badge" class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center border-2 border-white shadow-sm transition-all scale-100 <?php echo $itemCount > 0 ? '' : 'hidden'; ?>">
                        <?php echo $itemCount; ?>
                    </span>
                </a>
                <?php endif; ?>
                
                <?php if ($canUpdateProfile): ?>
                <div class="user-dropdown relative">
                    <button onclick="toggleDropdown()" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50 flex items-center gap-2">
                        <i class="fa-regular fa-user text-lg"></i>
                        <span class="text-sm font-medium text-slate-600 hidden sm:inline"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50">
                        <a href="/Campus-Food-Ordering-System/view/customer/profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                        <?php if ($canViewOrders): ?>
                        <a href="/Campus-Food-Ordering-System/view/customer/orders.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My Orders</a>
                        <?php endif; ?>
                        <hr class="my-1 border-slate-100">
                        <a href="/Campus-Food-Ordering-System/view/entrance/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-3">
                    <a href="/Campus-Food-Ordering-System/view/entrance/login.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 px-4 py-2 rounded-xl hover:bg-slate-50 interactive-transition">Log In</a>
                    <a href="/Campus-Food-Ordering-System/view/entrance/register.php" class="text-sm font-bold text-white bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl shadow-md shadow-emerald-500/10 interactive-transition hover:scale-105">Register</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script>
        function toggleDropdown() {
            const menu = document.getElementById('dropdownMenu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.getElementById('dropdownMenu').classList.add('hidden');
            }
        });
    </script>