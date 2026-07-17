<?php
/**
 * Admin Sidebar
 * 
 * @var array $currentUser - Current admin user
 * @var string $activePage - Active page for highlighting
 */

$activePage = $activePage ?? 'dashboard';
$currentUser = $currentUser ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Foodie Admin'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-<?php echo $activePage; ?>.css">
</head>
<body class="bg-gray-50 flex h-screen text-gray-800 antialiased">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-gray-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <!-- Logo -->
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-black mb-1">
                    <!-- <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-20 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-black"></i> -->
                               <script
  src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js"
  type="module"
></script>

<dotlottie-wc
  src="https://lottie.host/ea75b4fe-1d6d-4e5e-97eb-df01f2e490df/FTXFOlVlea.lottie"
  style="width: 55px;height: 55px"
  autoplay
  loop
></dotlottie-wc>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-black">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1"><p class="text-xs text-gray-400"><?php echo htmlspecialchars($currentUser['role'] ?? ' '); ?></p></span>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'dashboard' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'users' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'menu' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'orders' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-refunds.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'refunds' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-rotate-left text-lg w-6 text-center"></i>
                    <span>Refunds</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'reports' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-notifications.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'notifications' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-bell text-lg w-6 text-center"></i>
                    <span>Notifications</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'settings' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
                <a href="admin-profile.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 <?php echo $activePage === 'profile' ? 'active bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?> rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Profile</span>
                </a>
            </nav>
        </div>

        <!-- Bottom: User Info + Logout -->
        <div class="px-3">
            <a href="admin-profile.php" class="block hover:opacity-85 transition-opacity mb-2">
                <div class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-gray-50">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                        <?php echo strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></p>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($currentUser['role'] ?? ' '); ?></p>
                    </div>
                </div>
            </a>
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-red-50 hover:text-red-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT START ===== -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <div class="flex items-center justify-end px-8 py-4 border-b border-gray-100 bg-white flex-shrink-0 relative z-30">
            <?php getNotificationController()->widget(); ?>
        </div>
        <div class="flex-1 p-8 overflow-y-auto">