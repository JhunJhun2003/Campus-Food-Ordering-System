<?php
declare(strict_types=1);

session_start();

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

// Load vendor and auth helper first
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/auth_helper.php';
require_once __DIR__ . '/includes/permissions.php';

requireStaffAuth();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';

$permissions = getStaffPermissions($userId);

// Check if user has dashboard access
if (!$permissions['viewDashboard']) {
    $_SESSION['error'] = "You do not have permission to access the staff dashboard.";
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

// ============================================
// 2. BUSINESS LOGIC - GET DATA
// ============================================

// TODO: Replace with actual repository calls
$stats = [
    'totalOrders' => 0,
    'pendingOrders' => 0,
    'preparingOrders' => 0,
    'completedOrders' => 0,
];

$recentOrders = [];

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Staff Dashboard - Foodie';
$activePage = 'dashboard';
$customCss = 'css/staff-dashboard.css';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Staff Dashboard</h1>
            <p class="text-sm text-slate-500">Welcome back, <?php echo htmlspecialchars($userName); ?>!</p>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm text-slate-500">
                <i class="fa-regular fa-calendar mr-2"></i>
                <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php 
        $statsCards = [
            ['label' => 'Total Orders', 'value' => $stats['totalOrders'], 'icon' => 'fa-receipt', 'color' => 'indigo'],
            ['label' => 'Pending Orders', 'value' => $stats['pendingOrders'], 'icon' => 'fa-clock', 'color' => 'amber'],
            ['label' => 'Preparing', 'value' => $stats['preparingOrders'], 'icon' => 'fa-utensils', 'color' => 'blue'],
            ['label' => 'Completed', 'value' => $stats['completedOrders'], 'icon' => 'fa-check-circle', 'color' => 'emerald'],
        ];
        $colorClasses = [
            'indigo' => 'bg-indigo-50 text-indigo-600',
            'amber' => 'bg-amber-50 text-amber-600',
            'blue' => 'bg-blue-50 text-blue-600',
            'emerald' => 'bg-emerald-50 text-emerald-600',
        ];
        foreach ($statsCards as $card): 
        ?>
        <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500"><?php echo $card['label']; ?></p>
                    <p class="text-2xl font-bold text-slate-900 mt-1"><?php echo $card['value']; ?></p>
                </div>
                <div class="w-12 h-12 <?php echo $colorClasses[$card['color']]; ?> rounded-xl flex items-center justify-center">
                    <i class="fa-solid <?php echo $card['icon']; ?> text-xl"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
            <i class="fa-solid fa-bolt text-indigo-500"></i>
            <span>Quick Actions</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php if ($permissions['viewOrders']): ?>
            <a href="staff-orders.php" class="flex items-center space-x-3 p-4 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors">
                <i class="fa-solid fa-receipt text-indigo-600 text-xl"></i>
                <div>
                    <p class="font-semibold text-slate-900">View Orders</p>
                    <p class="text-sm text-slate-500">Manage all orders</p>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($permissions['viewMenu']): ?>
            <a href="staff-menu.php" class="flex items-center space-x-3 p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors">
                <i class="fa-solid fa-book-open text-emerald-600 text-xl"></i>
                <div>
                    <p class="font-semibold text-slate-900">Manage Menu</p>
                    <p class="text-sm text-slate-500">View food items</p>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($userRole === 'admin'): ?>
            <a href="../admin/admin-users.php" class="flex items-center space-x-3 p-4 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors">
                <i class="fa-regular fa-user text-purple-600 text-xl"></i>
                <div>
                    <p class="font-semibold text-slate-900">Manage Users</p>
                    <p class="text-sm text-slate-500">User management</p>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="mt-6 bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                <span>Recent Orders</span>
            </h2>
            <?php if ($permissions['viewOrders']): ?>
            <a href="staff-orders.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                View All <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <?php if (empty($recentOrders)): ?>
            <p class="text-slate-400 text-center py-8">No orders yet.</p>
            <?php else: ?>
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">#<?php echo $order['id']; ?></td>
                        <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td class="px-4 py-3 font-medium text-slate-900">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td class="px-4 py-3">
                            <span class="status-badge <?php echo $order['status_class'] ?? 'status-pending'; ?>">
                                <?php echo ucfirst($order['status_name'] ?? 'Pending'); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="staff-orders.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>