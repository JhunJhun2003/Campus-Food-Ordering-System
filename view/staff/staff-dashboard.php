<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

// Check if user has staff or admin role
if (!in_array($_SESSION['user_role'], ['staff', 'admin'])) {
    header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    exit();
}

// Get user info
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .sidebar-link.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }
        .sidebar-link:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
        .stat-card {
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="bg-[#F8FAFC] flex h-screen text-slate-800 antialiased overflow-hidden">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-slate-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-slate-900 mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-10 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-slate-950"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-slate-950">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Staff Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="staff-dashboard.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="staff-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="staff-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <?php if ($userRole === 'admin'): ?>
                <a href="../admin/admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="../admin/admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                    <p class="text-xs text-gray-400"><?php echo ucfirst($userRole); ?></p>
                </div>
            </div>
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
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
            <!-- Total Orders -->
            <div class="stat-card bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Total Orders</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="stat-card bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Pending Orders</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-clock text-amber-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Preparing Orders -->
            <div class="stat-card bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Preparing</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-utensils text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Orders -->
            <div class="stat-card bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Completed</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-check-circle text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

<!-- Quick Actions -->
<div class="bg-white border border-slate-100 rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
        <i class="fa-solid fa-bolt text-indigo-500"></i>
        <span>Quick Actions</span>
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="staff-orders.php" class="flex items-center space-x-3 p-4 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors">
            <i class="fa-solid fa-receipt text-indigo-600 text-xl"></i>
            <div>
                <p class="font-semibold text-slate-900">View Orders</p>
                <p class="text-sm text-slate-500">Manage all orders</p>
            </div>
        </a>
        <a href="staff-menu.php" class="flex items-center space-x-3 p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors">
            <i class="fa-solid fa-book-open text-emerald-600 text-xl"></i>
            <div>
                <p class="font-semibold text-slate-900">Manage Menu</p>
                <p class="text-sm text-slate-500">View food items</p>
            </div>
        </a>
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
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                    <span>Recent Orders</span>
                </h2>
            </div>
            <div class="p-6">
                <p class="text-slate-400 text-center py-8">No orders yet.</p>
            </div>
        </div>
    </main>

</body>
</html>