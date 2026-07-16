<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
if (!hasPermission('view_reports')) {
    renderAdminPermissionDeniedPage('Access denied', 'reports');
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$stats = $adminController->reports();
$currentUser = $adminController->getCurrentUser();

// Extract data with fallbacks
$totalOrders = $stats['total_orders'] ?? 0;
$totalRevenue = $stats['total_revenue'] ?? 0;
$completedOrders = $stats['completed_orders'] ?? 0;
$pendingOrders = $stats['pending_orders'] ?? 0;
$monthlyRevenue = $stats['monthly_revenue'] ?? [];

// Calculate max revenue for chart scaling
$maxRevenue = !empty($monthlyRevenue) ? max(array_column($monthlyRevenue, 'revenue')) : 1;
if ($maxRevenue <= 0) {
    $maxRevenue = 1;
}

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Reports';
$activePage = 'reports';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
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
    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Total Orders</p>
            </div>
            <div class="p-2 bg-blue-50 rounded-lg">
                <i class="fa-solid fa-receipt text-blue-500"></i>
            </div>
        </div>
        <div class="flex items-baseline space-x-2">
            <span class="text-3xl font-extrabold text-slate-900"><?php
           
            echo $totalOrders; ?></span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Revenue</p>
            </div>
            <div class="p-2 bg-emerald-50 rounded-lg">
                <i class="fa-solid fa-wallet text-emerald-500"></i>
            </div>
        </div>
        <div class="flex items-baseline space-x-2">
            <span class="text-3xl font-extrabold text-slate-900">$<?php echo number_format($totalRevenue, 2); ?></span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Completed Orders</p>
            </div>
            <div class="p-2 bg-amber-50 rounded-lg">
                <i class="fa-solid fa-circle-check text-amber-500"></i>
            </div>
        </div>
        <div class="flex items-baseline space-x-2">
            <span class="text-3xl font-extrabold text-slate-900"><?php echo $completedOrders; ?></span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-violet-600">Pending Orders</p>
            </div>
            <div class="p-2 bg-violet-50 rounded-lg">
                <i class="fa-solid fa-clock text-violet-500"></i>
            </div>
        </div>
        <div class="flex items-baseline space-x-2">
            <span class="text-3xl font-extrabold text-slate-900"><?php echo $pendingOrders; ?></span>
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

                <!-- Grid Lines -->
                <line x1="50" y1="30" x2="950" y2="30" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="50" y1="80" x2="950" y2="80" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="50" y1="130" x2="950" y2="130" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="50" y1="180" x2="950" y2="180" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="50" y1="210" x2="950" y2="210" stroke="#CBD5E1" stroke-width="1.5" />

                <!-- Y-Axis Labels -->
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
                
                <!-- Area Fill -->
                <path d="<?php echo $areaPath; ?>" fill="url(#chartGradient)" />
                
                <!-- Line -->
                <path d="<?php echo $pathD; ?>" fill="none" stroke="#4f46e5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" filter="url(#shadow)" />
                
                <!-- Data Points -->
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

<!-- Footer -->
<div class="mt-8 text-center text-xs text-slate-400">
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
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.05);
    }
</style>

</main>
</body>
</html>