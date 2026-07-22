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
    http_response_code(403);
    echo 'Access denied';
    exit();
}

$adminController = getAdminController();
$stats = $adminController->reports();

// Accept optional query params to include in filename and CSV
$startParam = $_GET['start'] ?? null;
$endParam = $_GET['end'] ?? null;
$groupParam = $_GET['group'] ?? 'day';

$monthly = $stats['monthly_revenue'] ?? [];
$totalOrders = $stats['total_orders'] ?? 0;
$totalRevenue = $stats['total_revenue'] ?? 0;
$completedOrders = $stats['completed_orders'] ?? 0;
$pendingOrders = $stats['pending_orders'] ?? 0;

// Build a filename that includes the optional range and grouping
$safeRange = '';
if ($startParam || $endParam) {
    $s = $startParam ? $startParam : 'start';
    $e = $endParam ? $endParam : 'end';
    $safeRange = "_{$s}_to_{$e}";
}
$filename = 'reports' . $safeRange . '_' . $groupParam . '_' . date('Ymd_His') . '.csv';

require_once __DIR__ . '/../../inc/settings_helper.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// UTF-8 BOM for Excel compatibility
fwrite($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Metric', 'Value']);
fputcsv($output, ['Total Orders', $totalOrders]);
fputcsv($output, ['Total Revenue', app_format_price($totalRevenue)]);
fputcsv($output, ['Completed Orders', $completedOrders]);
fputcsv($output, ['Pending Orders', $pendingOrders]);
fputcsv($output, []);
fputcsv($output, ['Grouped Revenue']);

// Label column depends on grouping
$labelCol = 'Period';
if ($groupParam === 'month') $labelCol = 'Month';
elseif ($groupParam === 'year') $labelCol = 'Year';
elseif ($groupParam === 'day') $labelCol = 'Date';

fputcsv($output, [$labelCol, 'Revenue']);

foreach ($monthly as $row) {
    $m = $row['month'] ?? '';
    $r = $row['revenue'] ?? 0;
    fputcsv($output, [$m, app_format_price($r)]);
}

fclose($output);
exit();
