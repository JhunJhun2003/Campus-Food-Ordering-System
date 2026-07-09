<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
requirePermission('view_dashboard');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$stats = $adminController->dashboard();
$currentUser = $adminController->getCurrentUser();

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Dashboard';
$activePage = 'dashboard';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-start mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?>! Here's your overview.</p>
    </div>
    <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-400"><?php echo date('l, F j, Y'); ?></span>
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
            <i class="fa-regular fa-bell text-lg"></i>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 font-medium">Total Users</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['total_users'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                <i class="fa-regular fa-user text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-xs text-green-600">
            <i class="fa-solid fa-arrow-up mr-1"></i>
            <span>12% this month</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 font-medium">Total Foods</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['total_foods'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                <i class="fa-solid fa-utensils text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-xs text-green-600">
            <i class="fa-solid fa-arrow-up mr-1"></i>
            <span>5 new this week</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 font-medium">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['total_orders'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="fa-solid fa-receipt text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-xs text-orange-600">
            <i class="fa-solid fa-arrow-up mr-1"></i>
            <span>8% this month</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 font-medium">Pending Orders</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $stats['pending_orders'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                <i class="fa-solid fa-clock text-xl"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center text-xs text-red-600">
            <i class="fa-solid fa-arrow-up mr-1"></i>
            <span>Needs attention</span>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-semibold text-gray-900">Recent Orders</h2>
        <a href="admin-orders.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            View All <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <?php if (empty($stats['recent_orders'])): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fa-regular fa-inbox text-4xl mb-3 block"></i>
                <p class="text-sm font-medium">No orders yet</p>
            </div>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/50 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                        <th class="px-6 py-3">Order ID</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($stats['recent_orders'] as $order): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900">$<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                        $status = strtolower($order['status_name']);
                                        echo match($status) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'accepted' => 'bg-blue-100 text-blue-800',
                                            'preparing' => 'bg-purple-100 text-purple-800',
                                            'ready' => 'bg-cyan-100 text-cyan-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>
                                ">
                                    <?php echo ucfirst($order['status_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400 text-xs"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<div class="mt-8 text-center text-xs text-gray-400">
    <p>&copy; <?php echo date('Y'); ?> FOODIE Admin Panel. All rights reserved.</p>
</div>

<style>
    .sidebar-link.active {
        background-color: #EEF2FF;
        color: #4F46E5;
    }
    .sidebar-link:hover {
        background-color: #F9FAFB;
        color: #111827;
    }
</style>

</main>
</body>
</html>