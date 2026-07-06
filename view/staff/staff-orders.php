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

$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';
$isAdmin = $userRole === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Orders - Foodie</title>
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-accepted { background: #DBEAFE; color: #1E40AF; }
        .status-preparing { background: #E0E7FF; color: #3730A3; }
        .status-ready { background: #D1FAE5; color: #065F46; }
        .status-completed { background: #D1FAE5; color: #065F46; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
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
                <a href="staff-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="staff-orders.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="staff-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <?php if ($isAdmin): ?>
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
                <h1 class="text-2xl font-bold text-slate-950">Orders Management</h1>
                <p class="text-sm text-slate-500">View and manage all customer orders</p>
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="all">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-sm">
                                <i class="fa-solid fa-inbox text-2xl block mb-2"></i>
                                No orders found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>