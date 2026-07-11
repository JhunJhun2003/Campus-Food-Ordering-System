<?php
/**
 * Staff Sidebar
 * 
 * @var array $permissions
 * @var string $activePage
 * @var string $userName
 * @var string $userRole
 * @var bool $isAdmin
 */

$navItems = [
    'dashboard' => [
        'url' => 'staff-dashboard.php',
        'icon' => 'fa-house',
        'label' => 'Dashboard',
        'permission' => true
    ],
    'orders' => [
        'url' => 'staff-orders.php',
        'icon' => 'fa-receipt',
        'label' => 'Orders',
        'permission' => $permissions['viewOrders'] ?? false
    ],
    'menu' => [
        'url' => 'staff-menu.php',
        'icon' => 'fa-book-open',
        'label' => 'Menu',
        'permission' => $permissions['viewMenu'] ?? false
    ],
    'users' => [
        'url' => '../admin/admin-users.php',
        'icon' => 'fa-user',
        'label' => 'Users',
        'permission' => $isAdmin
    ],
    'settings' => [
        'url' => '../admin/admin-settings.php',
        'icon' => 'fa-gear',
        'label' => 'Settings',
        'permission' => $isAdmin
    ],
    'profile' => [
        'url' => 'staff-profile.php',
        'icon' => 'fa-user',
        'label' => 'Profile',
        'permission' => $permissions['updateProfile'] ?? false
    ]
];
?>
<aside class="w-64 bg-white border-r border-slate-100 flex flex-col justify-between py-6 flex-shrink-0">
    <div>
        <!-- Logo -->
        <div class="flex flex-col items-center justify-center mb-8 px-6">
            <div class="relative flex items-center justify-center text-slate-900 mb-2">
                <span class="fa-stack fa-xl">
                    <!-- <i class="fa-solid fa-building fa-stack-2x opacity-10 -translate-y-1"></i>
                    <i class="fa-solid fa-hamburger fa-stack-1x text-slate-950"></i> -->
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
            <span class="text-xl font-black tracking-wider text-slate-950">FOODIE</span>
            <span class="text-xs text-gray-400 font-medium mt-1">Staff Panel</span>
        </div>

        <!-- Navigation -->
        <nav class="space-y-1 px-3">
            <?php foreach ($navItems as $key => $item): ?>
                <?php if ($item['permission']): ?>
                    <a href="<?php echo $item['url']; ?>" 
                       class="sidebar-link flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors <?php echo $activePage === $key ? 'active bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <i class="fa-solid <?php echo $item['icon']; ?> text-lg w-6 text-center"></i>
                        <span><?php echo $item['label']; ?></span>
                        <?php if ($activePage === $key): ?>
                            <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- User Info & Logout -->
    <div class="px-3">
        <?php if ($permissions['updateProfile'] ?? false): ?>
        <a href="staff-profile.php" class="block hover:opacity-85 transition-opacity">
        <?php endif; ?>
        <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                <p class="text-xs text-gray-400"><?php echo ucfirst($userRole); ?></p>
            </div>
        </div>
        <?php if ($permissions['updateProfile'] ?? false): ?>
        </a>
        <?php endif; ?>
        
        <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded-lg font-medium transition-colors">
            <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<style>
.sidebar-link {
    position: relative;
}
.sidebar-link.active {
    background-color: #EEF2FF;
    color: #4F46E5;
}
.sidebar-link:hover:not(.active) {
    background-color: #F9FAFB;
    color: #111827;
}
</style>