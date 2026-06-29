<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;

$adminController = new AdminController();
$stats = $adminController->dashboard();
$currentUser = $adminController->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-dashboard.css">
</head>
<body class="bg-gray-50 flex h-screen text-gray-800 antialiased">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-gray-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <!-- Logo -->
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-black mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-20 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-black"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-black">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>

                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>

                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>

                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>

                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>

                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <!-- Bottom: User Info + Logout -->
        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-400">Administrator</p>
                </div>
            </div>
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-red-50 hover:text-red-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
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
            <div class="stat-card bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

            <div class="stat-card bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

            <div class="stat-card bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

            <div class="stat-card bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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
                                    <td class="px-6 py-4 font-medium text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></td>
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
            <p>© <?php echo date('Y'); ?> FOODIE Admin Panel. All rights reserved.</p>
        </div>
    </main>

</body>
</html>