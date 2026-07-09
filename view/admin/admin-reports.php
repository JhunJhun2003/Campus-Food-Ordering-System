<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('view_reports');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use helper - NO 'new' keyword!
$adminController = getAdminController();
$stats = $adminController->reports();
$currentUser = $adminController->getCurrentUser();

// Extract data
$totalOrders = $stats['total_orders'] ?? 0;
$totalRevenue = $stats['total_revenue'] ?? 0;
$completedOrders = $stats['completed_orders'] ?? 0;
$pendingOrders = $stats['pending_orders'] ?? 0;
$monthlyRevenue = $stats['monthly_revenue'] ?? [];

// Calculate max revenue for chart scaling
$maxRevenue = !empty($monthlyRevenue) ? max(array_column($monthlyRevenue, 'revenue')) : 1;

// ... rest of the HTML remains the same ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Reports Overview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-reports.css">
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
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
                <a href="admin-profile.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Profile</span>
                </a>
            </nav>
        </div>

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
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 flex flex-col overflow-y-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">Reports</h1>
                <p class="text-sm text-slate-500">Track your business sales, growth, and metrics here.</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="inline-flex items-center space-x-2.5 px-4 py-2.5 bg-white border border-slate-200 hover:border-slate-300 rounded-lg text-sm font-medium text-slate-700 shadow-sm transition-all">
                    <i class="fa-regular fa-calendar-days text-slate-400"></i>
                    <span>Last 30 Days</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                </button>
                <button class="inline-flex items-center space-x-2 px-4 py-2.5 bg-white border border-slate-200 hover:border-indigo-100 hover:bg-indigo-50/30 rounded-lg text-sm font-semibold text-slate-700 shadow-sm transition-all group">
                    <i class="fa-solid fa-arrow-up-from-bracket text-slate-400 group-hover:text-indigo-600"></i>
                    <span>Export</span>
                </button>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            
            <!-- Total Orders (Blue) -->
            <div class="stat-card bg-white border border-slate-100/80 rounded-xl p-5 shadow-sm relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-blue-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">Total Orders</span>
                    <span class="p-2 bg-blue-50 text-blue-500 rounded-lg"><i class="fa-solid fa-truck-ramp-box"></i></span>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-slate-900"><?php echo $totalOrders; ?></span>
                    <span class="text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-circle-arrow-up mr-0.5"></i>+12.4%</span>
                </div>
            </div>

            <!-- Total Revenue (Green) -->
            <div class="stat-card bg-white border border-slate-100/80 rounded-xl p-5 shadow-sm relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-emerald-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Total Revenue</span>
                    <span class="p-2 bg-emerald-50 text-emerald-500 rounded-lg"><i class="fa-solid fa-wallet"></i></span>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-slate-900">$<?php echo number_format($totalRevenue, 0); ?></span>
                    <span class="text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-circle-arrow-up mr-0.5"></i>+8.2%</span>
                </div>
            </div>

            <!-- Completed Orders (Orange) - REPLACED Active Users -->
            <div class="stat-card bg-white border border-slate-100/80 rounded-xl p-5 shadow-sm relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-amber-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md">Completed Orders</span>
                    <span class="p-2 bg-amber-50 text-amber-500 rounded-lg"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-slate-900"><?php echo $completedOrders; ?></span>
                    <span class="text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-circle-arrow-up mr-0.5"></i>+5.3%</span>
                </div>
            </div>

            <!-- Pending Orders (Purple) -->
            <div class="stat-card bg-white border border-slate-100/80 rounded-xl p-5 shadow-sm relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-24 w-24 bg-violet-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-600 bg-violet-50 px-2.5 py-1 rounded-md">Pending Orders</span>
                    <span class="p-2 bg-violet-50 text-violet-500 rounded-lg"><i class="fa-solid fa-clock-rotate-left"></i></span>
                </div>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-extrabold text-slate-900"><?php echo $pendingOrders; ?></span>
                    <span class="text-xs text-slate-400 font-medium"><i class="fa-solid fa-minus mr-0.5"></i>No change</span>
                </div>
            </div>

        </div>

        <!-- Sales Chart -->
        <div class="bg-white border border-slate-100 rounded-xl shadow-sm p-6 flex flex-col space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Sales Overview</h2>
                    <p class="text-xs text-slate-400">Monthly revenue trends</p>
                </div>
                <span class="inline-flex items-center space-x-1.5 px-3 py-1 bg-indigo-50/50 rounded-full text-xs font-semibold text-indigo-600">
                    <span class="h-2 w-2 bg-indigo-600 rounded-full"></span>
                    <span>Revenue ($)</span>
                </span>
            </div>

            <div class="relative w-full aspect-[21/9] min-h-[250px] bg-slate-50/30 rounded-lg border border-slate-100 p-4">
                <?php if (empty($monthlyRevenue)): ?>
                    <div class="flex items-center justify-center h-full text-slate-400">
                        <div class="text-center">
                            <i class="fa-regular fa-chart-bar text-4xl block mb-3"></i>
                            <p class="text-sm font-medium">No revenue data available</p>
                        </div>
                    </div>
                <?php else: ?>
                    <svg viewBox="0 0 1000 240" class="w-full h-full overflow-visible">
                        <defs>
                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#4f46e5" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#4f46e5" stop-opacity="0.00" />
                            </linearGradient>
                            <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
                                <feDropShadow dx="0" dy="8" stdDeviation="4" flood-color="#4f46e5" flood-opacity="0.15" />
                            </filter>
                        </defs>

                        <!-- Grid -->
                        <line x1="50" y1="30" x2="950" y2="30" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                        <line x1="50" y1="80" x2="950" y2="80" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                        <line x1="50" y1="130" x2="950" y2="130" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                        <line x1="50" y1="180" x2="950" y2="180" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                        <line x1="50" y1="210" x2="950" y2="210" stroke="#CBD5E1" stroke-width="1.5" />

                        <!-- Y Labels -->
                        <text x="25" y="34" class="fill-slate-400 font-semibold text-[11px]" text-anchor="middle">$<?php echo number_format($maxRevenue, 0); ?></text>
                        <text x="25" y="84" class="fill-slate-400 font-semibold text-[11px]" text-anchor="middle">$<?php echo number_format($maxRevenue * 0.66, 0); ?></text>
                        <text x="25" y="134" class="fill-slate-400 font-semibold text-[11px]" text-anchor="middle">$<?php echo number_format($maxRevenue * 0.33, 0); ?></text>
                        <text x="25" y="184" class="fill-slate-400 font-semibold text-[11px]" text-anchor="middle">$0</text>

                        <?php
                        $count = count($monthlyRevenue);
                        $chartWidth = 900;
                        $chartHeight = 180;
                        $xStep = $count > 1 ? $chartWidth / ($count - 1) : $chartWidth;
                        
                        $points = [];
                        foreach ($monthlyRevenue as $i => $data) {
                            $x = 50 + ($i * $xStep);
                            $y = 210 - (($data['revenue'] / $maxRevenue) * $chartHeight);
                            $points[] = ['x' => $x, 'y' => $y, 'revenue' => $data['revenue'], 'month' => $data['month']];
                        }
                        
                        $pathD = '';
                        foreach ($points as $i => $p) {
                            if ($i === 0) $pathD = "M {$p['x']} {$p['y']}";
                            else $pathD .= " L {$p['x']} {$p['y']}";
                        }
                        $areaPath = $pathD . " L {$points[$count-1]['x']} 210 L {$points[0]['x']} 210 Z";
                        ?>
                        
                        <path d="<?php echo $areaPath; ?>" fill="url(#chartGradient)" />
                        <path d="<?php echo $pathD; ?>" fill="none" stroke="#4f46e5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" filter="url(#shadow)" />
                        
                        <?php foreach ($points as $p): ?>
                            <circle cx="<?php echo $p['x']; ?>" cy="<?php echo $p['y']; ?>" r="5" class="fill-white stroke-indigo-600 stroke-[3px]" />
                            <text x="<?php echo $p['x']; ?>" y="232" class="fill-slate-500 font-bold text-xs" text-anchor="middle"><?php echo $p['month']; ?></text>
                            <?php if ($p['revenue'] > ($maxRevenue * 0.7)): ?>
                                <rect x="<?php echo $p['x'] - 25; ?>" y="<?php echo $p['y'] - 30; ?>" width="50" height="20" rx="4" class="fill-slate-900 shadow-sm" />
                                <text x="<?php echo $p['x']; ?>" y="<?php echo $p['y'] - 16; ?>" class="fill-white font-bold text-[10px]" text-anchor="middle">$<?php echo number_format($p['revenue'], 0); ?></text>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </svg>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>