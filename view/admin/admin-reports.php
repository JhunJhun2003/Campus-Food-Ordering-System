<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/settings_helper.php';

requireLogin();
if (!hasPermission('view_reports')) {
    renderAdminPermissionDeniedPage('Access denied', 'reports');
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();

// Get filter parameters
$period = $_GET['period'] ?? 'last30';
$group = $_GET['group'] ?? 'day';
$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;
$yearParam = $_GET['year'] ?? null;
$monthParam = $_GET['month'] ?? null;

// Calculate date range based on period
$startDate = null;
$endDate = null;
$subtitle = '';

switch ($period) {
    case 'last30':
        $end = new DateTime();
        $start = new DateTime();
        $start->modify('-29 days');
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $subtitle = 'Last 30 Days';
        break;
        
    case 'this_month':
        $start = new DateTime('first day of this month');
        $end = new DateTime('last day of this month');
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $subtitle = date('F Y');
        break;
        
    case 'this_year':
        $start = new DateTime('first day of January ' . date('Y'));
        $end = new DateTime('last day of December ' . date('Y'));
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $subtitle = 'Year ' . date('Y');
        break;
        
    case 'custom':
        if ($startParam && $endParam) {
            $startDate = $startParam;
            $endDate = $endParam;
            $displayRange = date('M j, Y', strtotime($startParam)) . ' — ' . date('M j, Y', strtotime($endParam));
            $subtitle = 'Custom: ' . $displayRange;
        } else {
            $end = new DateTime();
            $start = new DateTime();
            $start->modify('-29 days');
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');
            $subtitle = 'Last 30 Days';
        }
        break;
        
    case 'month_year':
        if ($monthParam && $yearParam) {
            $start = new DateTime($yearParam . '-' . $monthParam . '-01');
            $end = new DateTime($start->format('Y-m-t'));
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');
            $subtitle = date('F Y', strtotime($yearParam . '-' . $monthParam . '-01'));
        } else {
            $start = new DateTime('first day of this month');
            $end = new DateTime('last day of this month');
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');
            $subtitle = date('F Y');
        }
        break;
        
    default:
        $end = new DateTime();
        $start = new DateTime();
        $start->modify('-29 days');
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $subtitle = 'Last 30 Days';
}

// ✅ Pass filters including period and group to reports method
$stats = $adminController->reports([
    'start' => $startDate,
    'end' => $endDate,
    'group' => $group,
    'period' => $period,
    'month' => $monthParam,
    'year' => $yearParam
]);

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

// Get currency symbol for JavaScript
$currencySymbol = app_currency_symbol();

// Get available years for dropdown
$currentYear = date('Y');
$years = range($currentYear - 5, $currentYear);

// Get months for dropdown
$months = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Reports';
$activePage = 'reports';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Reports</h1>
        <p class="text-sm text-slate-500">Monitor your business performance at a glance</p>
    </div>
    <a id="exportBtn" href="/Campus-Food-Ordering-System/view/admin/export-reports.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
        <i class="fa-solid fa-download text-slate-400"></i>
        Export
    </a>
</div>

<!-- ============================================ -->
<!-- METRICS CARDS -->
<!-- ============================================ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Total Orders</span>
            <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                <i class="fa-solid fa-receipt text-sm"></i>
            </span>
        </div>
        <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($totalOrders); ?></p>
        <span class="text-xs text-slate-400">All time</span>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Revenue</span>
            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-wallet text-sm"></i>
            </span>
        </div>
        <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo app_format_price($totalRevenue); ?></p>
        <span class="text-xs text-slate-400">Total earned</span>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Completed</span>
            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-circle-check text-sm"></i>
            </span>
        </div>
        <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($completedOrders); ?></p>
        <span class="text-xs text-slate-400">Delivered orders</span>
    </div>

    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Pending</span>
            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                <i class="fa-solid fa-clock text-sm"></i>
            </span>
        </div>
        <p class="text-2xl font-bold text-slate-900 mt-2"><?php echo number_format($pendingOrders); ?></p>
        <span class="text-xs text-slate-400">Awaiting action</span>
    </div>
</div>

<!-- ============================================ -->
<!-- FILTER BAR -->
<!-- ============================================ -->
<div class="mt-6 bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-slate-500 mr-1">Quick filters:</span>
            <button class="period-btn px-4 py-1.5 rounded-lg text-sm font-medium transition-all <?php echo $period === 'last30' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?>" data-period="last30">
                Last 30 Days
            </button>
            <button class="period-btn px-4 py-1.5 rounded-lg text-sm font-medium transition-all <?php echo $period === 'this_month' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?>" data-period="this_month">
                This Month
            </button>
            <button class="period-btn px-4 py-1.5 rounded-lg text-sm font-medium transition-all <?php echo $period === 'this_year' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?>" data-period="this_year">
                This Year
            </button>
        </div>
    </div>
    
    <div class="p-4 bg-slate-50/50 flex flex-wrap items-center gap-4">
        <!-- Month/Year Selector -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">Month:</span>
            <select id="monthSelect" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Select</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php echo $monthParam == $num ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="yearSelect" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">Select</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $yearParam == $year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="applyMonthYear" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-all">
                Apply
            </button>
        </div>

        <div class="hidden sm:block w-px h-6 bg-slate-200"></div>

        <!-- Date Range -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">From:</span>
            <input type="date" id="startDate" value="<?php echo $startDate; ?>" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <span class="text-sm text-slate-500">To:</span>
            <input type="date" id="endDate" value="<?php echo $endDate; ?>" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            <button id="applyCustomRange" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-all">
                Apply
            </button>
        </div>

        <div class="hidden sm:block w-px h-6 bg-slate-200"></div>

        <!-- Group By -->
        <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500">Group by:</span>
            <select id="groupSelect" class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="day" <?php echo $group === 'day' ? 'selected' : ''; ?>>Day</option>
                <option value="week" <?php echo $group === 'week' ? 'selected' : ''; ?>>Week</option>
                <option value="month" <?php echo $group === 'month' ? 'selected' : ''; ?>>Month</option>
                <option value="year" <?php echo $group === 'year' ? 'selected' : ''; ?>>Year</option>
            </select>
        </div>
    </div>
    
    <!-- Active Filter Display -->
    <div class="px-4 py-2 bg-indigo-50/30 border-t border-slate-100 flex items-center gap-2">
        <span class="text-xs font-medium text-slate-500">Showing:</span>
        <span class="text-xs font-medium text-slate-700 bg-white px-3 py-1 rounded-full border border-slate-200">
            <?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <span class="text-xs text-slate-400">|</span>
        <span class="text-xs text-slate-400">Grouped by <span class="font-medium text-slate-600"><?php echo ucfirst($group); ?></span></span>
    </div>
</div>

<script>
// ============================================
// FILTER FUNCTIONALITY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const periodBtns = document.querySelectorAll('.period-btn');
    const groupSelect = document.getElementById('groupSelect');
    const applyMonthYear = document.getElementById('applyMonthYear');
    const applyCustomRange = document.getElementById('applyCustomRange');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const exportLink = document.getElementById('exportBtn');

    // Period buttons
    periodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const period = this.dataset.period;
            const params = new URLSearchParams(window.location.search);
            params.set('period', period);
            params.delete('start');
            params.delete('end');
            params.delete('month');
            params.delete('year');
            if (groupSelect) params.set('group', groupSelect.value);
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    });

    // Apply Month/Year
    applyMonthYear.addEventListener('click', function() {
        const month = monthSelect.value;
        const year = yearSelect.value;
        if (!month || !year) {
            alert('Please select both month and year');
            return;
        }
        const params = new URLSearchParams(window.location.search);
        params.set('period', 'month_year');
        params.set('month', month);
        params.set('year', year);
        params.delete('start');
        params.delete('end');
        if (groupSelect) params.set('group', groupSelect.value);
        window.location.href = window.location.pathname + '?' + params.toString();
    });

    // Apply Custom Range
    applyCustomRange.addEventListener('click', function() {
        const start = startDate.value;
        const end = endDate.value;
        if (!start || !end) {
            alert('Please select both start and end dates');
            return;
        }
        if (start > end) {
            alert('Start date cannot be after end date');
            return;
        }
        const params = new URLSearchParams(window.location.search);
        params.set('period', 'custom');
        params.set('start', start);
        params.set('end', end);
        params.delete('month');
        params.delete('year');
        if (groupSelect) params.set('group', groupSelect.value);
        window.location.href = window.location.pathname + '?' + params.toString();
    });

    // Group by change
    if (groupSelect) {
        groupSelect.addEventListener('change', function() {
            const params = new URLSearchParams(window.location.search);
            params.set('group', this.value);
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    }

    // Export link - preserve query parameters
    if (exportLink) {
        const qs = window.location.search;
        if (qs) {
            exportLink.href = exportLink.href + qs;
        }
    }

    // Enter key support for date inputs
    startDate.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyCustomRange.click();
    });
    endDate.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') applyCustomRange.click();
    });
});
</script>

<!-- ============================================ -->
<!-- CHART SECTION -->
<!-- ============================================ -->
<div class="mt-6 bg-white rounded-xl border border-slate-100 shadow-sm p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Revenue Overview</h2>
            <p class="text-sm text-slate-400"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                <span>Revenue</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-3 h-3 rounded-full bg-indigo-200"></span>
                <span>Area</span>
            </div>
        </div>
    </div>

    <div class="relative w-full h-72 bg-slate-50/50 rounded-lg border border-slate-100 p-4">
        <?php if (empty($monthlyRevenue)): ?>
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="text-5xl mb-3">📊</div>
                    <p class="text-sm font-medium text-slate-500">No revenue data available</p>
                    <p class="text-xs text-slate-400 mt-1">Complete orders to see your revenue</p>
                </div>
            </div>
        <?php else: ?>
            <svg viewBox="0 0 1000 280" class="w-full h-full overflow-visible">
                <defs>
                    <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.25" />
                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.00" />
                    </linearGradient>
                    <filter id="chartShadow">
                        <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#6366f1" flood-opacity="0.15" />
                    </filter>
                </defs>

                <!-- Grid Lines -->
                <line x1="60" y1="40" x2="940" y2="40" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="60" y1="100" x2="940" y2="100" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="60" y1="160" x2="940" y2="160" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="60" y1="220" x2="940" y2="220" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="4 4" />
                <line x1="60" y1="250" x2="940" y2="250" stroke="#CBD5E1" stroke-width="1.5" />

                <!-- Y-Axis Labels -->
                <text x="45" y="44" class="fill-slate-400 font-medium text-[10px]" text-anchor="end"><?php echo app_format_price($maxRevenue); ?></text>
                <text x="45" y="104" class="fill-slate-400 font-medium text-[10px]" text-anchor="end"><?php echo app_format_price($maxRevenue * 0.66); ?></text>
                <text x="45" y="164" class="fill-slate-400 font-medium text-[10px]" text-anchor="end"><?php echo app_format_price($maxRevenue * 0.33); ?></text>
                <text x="45" y="224" class="fill-slate-400 font-medium text-[10px]" text-anchor="end"><?php echo app_format_price(0); ?></text>

                <?php
                $count = count($monthlyRevenue);
                $chartWidth = 880;
                $chartHeight = 210;
                $startX = 60;
                $bottomY = 250;
                
                // Determine step for labels
                $maxLabels = 15;
                $step = ceil($count / $maxLabels);
                if ($step < 1) $step = 1;
                
                $points = [];
                foreach ($monthlyRevenue as $i => $data) {
                    $x = $startX + (($i / max($count - 1, 1)) * $chartWidth);
                    $y = $bottomY - (($data['revenue'] / $maxRevenue) * $chartHeight);
                    $points[] = [
                        'x' => $x, 
                        'y' => $y, 
                        'revenue' => $data['revenue'], 
                        'month' => $data['month'],
                        'index' => $i
                    ];
                }
                
                $pathD = '';
                foreach ($points as $i => $p) {
                    if ($i === 0) $pathD = "M {$p['x']} {$p['y']}";
                    else $pathD .= " L {$p['x']} {$p['y']}";
                }
                $areaPath = $pathD . " L {$points[$count-1]['x']} {$bottomY} L {$points[0]['x']} {$bottomY} Z";
                ?>
                
                <!-- Area Fill -->
                <path d="<?php echo $areaPath; ?>" fill="url(#chartGradient)" />
                
                <!-- Line -->
                <path d="<?php echo $pathD; ?>" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" filter="url(#chartShadow)" />
                
                <!-- Data Points -->
                <?php foreach ($points as $p): 
                    $showLabel = ($p['index'] % $step === 0) || ($p['index'] === $count - 1);
                    $showTooltip = $p['revenue'] > 0;
                ?>
                    <!-- Data Point Circle -->
                    <circle cx="<?php echo $p['x']; ?>" cy="<?php echo $p['y']; ?>" r="4.5" class="fill-white stroke-indigo-600 stroke-[2.5] cursor-pointer" />
                    
                    <!-- X-Axis Label -->
                    <?php if ($showLabel): ?>
                        <text x="<?php echo $p['x']; ?>" y="<?php echo $bottomY + 20; ?>" class="fill-slate-500 font-medium text-[10px]" text-anchor="middle">
                            <?php 
                            $label = $p['month'];
                            // Shorten if too long
                            if (strlen($label) > 10) {
                                $parts = explode(' ', $label);
                                $label = $parts[0] ?? $label;
                            }
                            echo $label; 
                            ?>
                        </text>
                    <?php endif; ?>
                    
                    <!-- Tooltip for values with revenue -->
                    <?php if ($showTooltip): ?>
                        <rect x="<?php echo $p['x'] - 32; ?>" y="<?php echo $p['y'] - 38; ?>" width="64" height="24" rx="6" class="fill-slate-800 shadow-md" />
                        <text x="<?php echo $p['x']; ?>" y="<?php echo $p['y'] - 22; ?>" class="fill-white font-semibold text-[10px]" text-anchor="middle"><?php echo app_format_price($p['revenue']); ?></text>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
        <?php endif; ?>
    </div>
    
    <!-- Chart Summary -->
    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-100">
        <div class="flex items-center gap-4 text-xs text-slate-500">
            <span>📈 <span class="font-medium text-slate-700"><?php echo count($monthlyRevenue); ?></span> periods</span>
            <span>💰 <span class="font-medium text-slate-700"><?php echo app_format_price(array_sum(array_column($monthlyRevenue, 'revenue'))); ?></span> total</span>
            <span>📦 <span class="font-medium text-slate-700"><?php echo array_sum(array_column($monthlyRevenue, 'orders')); ?></span> orders</span>
        </div>
        <div class="text-xs text-slate-400">
            <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Data updates in real-time</span>
            </span>
        </div>
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
    .period-btn {
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
    }
    .period-btn:hover:not(.bg-indigo-600) {
        background-color: #e2e8f0;
    }
</style>

</main>
</body>
</html>