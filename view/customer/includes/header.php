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
$cssHref = str_starts_with($customCss, '/')
    ? $customCss
    : '/Campus-Food-Ordering-System/view/customer/' . $customCss;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Get user data for the header
// ============================================

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../inc/auth_helper.php';
require_once __DIR__ . '/../../../inc/order_helpers.php';
require_once __DIR__ . '/../../../inc/notification_helpers.php';
require_once __DIR__ . '/../../../inc/settings_helper.php';

use App\User\Presentation\Http\Controllers\UserController;

$userController = getUserController();
$isLoggedIn = $userController->isLoggedIn();
$currentUser = null;
$itemCount = 0;

if ($isLoggedIn) {
    $currentUser = $userController->getCurrentUser();
    $userId = $currentUser['id'] ?? null;
    if ($userId) {
        try {
            $cartController = getCartController();
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

// Get currency symbol from settings
$currencySymbol = app_currency_symbol();
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssHref); ?>">
    
    <script>
        // Global app settings for JavaScript
        window.AppSettings = {
            currencySymbol: '<?php echo $currencySymbol; ?>',
            siteName: '<?php echo app_site_name(); ?>',
            timezone: '<?php echo app_timezone(); ?>'
        };
        
        // Helper function for formatting prices in JavaScript
        function formatPrice(price) {
            return window.AppSettings.currencySymbol + parseFloat(price).toFixed(2);
        }
    </script>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- ===== HEADER ===== -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40 shadow-sm shadow-slate-100/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <!-- Brand Logo -->
            <a href="/Campus-Food-Ordering-System/" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-950">
                    <script
                      src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js"
                      type="module"
                    ></script>

                    <dotlottie-wc
                      src="https://lottie.host/ea75b4fe-1d6d-4e5e-97eb-df01f2e490df/FTXFOlVlea.lottie"
                      style="width: 30px;height: 55px"
                      autoplay
                      loop
                    ></dotlottie-wc>
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

                <?php if ($isLoggedIn): ?>
                    <?php getNotificationController()->widget(); ?>
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
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            const menu = document.getElementById('dropdownMenu');
            if (menu && !e.target.closest('.user-dropdown')) {
                menu.classList.add('hidden');
            }
        });
    </script>