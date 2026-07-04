<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;
use App\Order\Presentation\Http\Controllers\OrderController;

$userController = new UserController();

// Check if user is logged in
if (!$userController->isLoggedIn()) {
    header('Location: ../entrance/login.php');
    exit();
}

$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'];

// Get orders from OrderController
$orderController = new OrderController();
$orders = $orderController->getUserOrders($userId);
$statuses = $orderController->getStatuses();

// Group orders by status for filtering
$groupedOrders = [
    'ongoing' => [],
    'completed' => [],
    'cancelled' => []
];

foreach ($orders as $order) {
    $statusName = strtolower($order['status_name'] ?? 'pending');
    if (in_array($statusName, ['pending', 'accepted', 'preparing', 'ready'])) {
        $groupedOrders['ongoing'][] = $order;
    } elseif ($statusName === 'completed') {
        $groupedOrders['completed'][] = $order;
    } elseif ($statusName === 'cancelled') {
        $groupedOrders['cancelled'][] = $order;
    }
}

// Get status colors for badges
$statusColors = [
    'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'accepted' => 'bg-blue-50 border-blue-200 text-blue-700',
    'preparing' => 'bg-purple-50 border-purple-200 text-purple-700',
    'ready' => 'bg-cyan-50 border-cyan-200 text-cyan-700',
    'completed' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
    'cancelled' => 'bg-rose-50 border-rose-100 text-rose-600'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - My Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #FCFDFE;
        }
        .interactive-transition {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .order-card {
            animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .live-pulse {
            animation: pulse-ring 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .4; transform: scale(1.15); }
        }
        .alert-empty {
            background-color: #F8FAFC;
            border: 2px dashed #E2E8F0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    <!-- HEADER -->
    <header class="bg-white border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center space-x-3 group">
                <div class="relative flex items-center justify-center text-slate-950">
                    <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 interactive-transition">
                        <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                        <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                        <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
            </a>

            <nav class="hidden md:flex items-center space-x-10">
                <a href="dashboard.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Home</a>
                <a href="cart.php" class="text-sm font-semibold text-slate-600 hover:text-emerald-500 interactive-transition">Cart</a>
                <a href="orders.php" class="text-sm font-bold text-emerald-500 border-b-2 border-emerald-500 pb-1.5 interactive-transition">Orders</a>
            </nav>

            <div class="flex items-center space-x-6">
                <button class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-magnifying-glass text-lg"></i>
                </button>
                <a href="cart.php" class="relative text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-solid fa-cart-shopping text-lg"></i>
                </a>
                <a href="profile.php" class="text-slate-700 hover:text-emerald-500 interactive-transition p-2 rounded-full hover:bg-slate-50">
                    <i class="fa-regular fa-user text-lg"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Welcome Frame -->
        <div class="mb-10 text-center sm:text-left flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">My Orders</h1>
                <p class="text-sm text-slate-500 mt-1">Track ongoing deliveries and view past orders.</p>
            </div>
            
            <div class="flex items-center justify-center sm:justify-start gap-4 bg-slate-50 border border-slate-100 p-2 rounded-2xl">
                <div class="px-4 py-2 bg-white rounded-xl shadow-sm border border-slate-100 text-center min-w-[90px]">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide">Active</span>
                    <span class="text-lg font-extrabold text-emerald-600"><?php echo count($groupedOrders['ongoing']); ?> Orders</span>
                </div>
                <div class="px-4 py-2 bg-white rounded-xl shadow-sm border border-slate-100 text-center min-w-[90px]">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide">Delivered</span>
                    <span class="text-lg font-extrabold text-slate-800"><?php echo count($groupedOrders['completed']); ?> total</span>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center space-x-2 border-b border-slate-100 pb-px mb-8 overflow-x-auto whitespace-nowrap">
            <button onclick="filterOrders('all')" id="tab-all" class="px-5 py-3 border-b-2 border-emerald-500 font-bold text-sm text-emerald-600 hover:text-emerald-500 transition-colors">
                All Orders
            </button>
            <button onclick="filterOrders('ongoing')" id="tab-ongoing" class="px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors">
                Ongoing
            </button>
            <button onclick="filterOrders('completed')" id="tab-completed" class="px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors">
                Completed
            </button>
            <button onclick="filterOrders('cancelled')" id="tab-cancelled" class="px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors">
                Cancelled
            </button>
        </div>

        <!-- ORDERS LIST -->
        <div class="space-y-6" id="orders-list-container">
            
            <?php if (empty($orders)): ?>
                <!-- Empty State -->
                <div class="alert-empty rounded-2xl p-12 text-center">
                    <div class="text-slate-300 text-5xl mb-4">
                        <i class="fa-regular fa-receipt"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">No orders yet</h3>
                    <p class="text-slate-400 text-sm mt-1 mb-6">Start ordering delicious food from our menu!</p>
                    <a href="dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-lg text-sm interactive-transition">
                        <span>Browse Menu</span>
                    </a>
                </div>
            <?php else: ?>
                <!-- Ongoing Orders -->
                <?php foreach ($groupedOrders['ongoing'] as $order): ?>
                    <div class="order-card bg-white border border-slate-150 rounded-2xl shadow-sm shadow-slate-100/60 overflow-hidden" data-status="ongoing">
                        <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span class="text-sm font-bold text-slate-900">Order ID: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="text-xs text-slate-400 font-medium">Placed <?php echo date('M j, Y, h:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="flex items-center space-x-2 <?php echo $statusColors[$order['status_name']] ?? 'bg-slate-50 border-slate-200 text-slate-700'; ?> px-3.5 py-1.5 rounded-full text-xs font-bold">
                                <span class="w-2 h-2 rounded-full live-pulse" style="background-color: <?php echo $order['status_name'] === 'preparing' ? '#F59E0B' : '#3B82F6'; ?>"></span>
                                <span><?php echo ucfirst($order['status_name']); ?></span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">
                                    <?php echo $order['item_emoji'] ?? '🍔'; ?>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-slate-900"><?php echo htmlspecialchars($order['item_names'] ?? 'Food Items'); ?></h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Quantity: <?php echo $order['total_items'] ?? 0; ?> items</p>
                                    <button onclick="toggleDetails('details-<?php echo $order['id']; ?>')" class="text-xs font-bold text-emerald-500 hover:text-emerald-600 mt-2 flex items-center gap-1 focus:outline-none">
                                        <span>View details</span>
                                        <i class="fa-solid fa-chevron-down text-[10px]" id="icon-details-<?php echo $order['id']; ?>"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="text-left md:text-right pr-4">
                                    <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                    <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <?php if ($order['status_name'] === 'ready' || $order['status_name'] === 'preparing'): ?>
                                    <button onclick="openLiveTracking('#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>', <?php echo $order['status_name'] === 'ready' ? 2 : 1; ?>)" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-5 py-3 rounded-xl text-xs shadow-md shadow-emerald-500/15 hover:shadow-emerald-500/30 interactive-transition flex items-center justify-center space-x-2">
                                        <i class="fa-solid fa-location-crosshairs"></i>
                                        <span>Track Live</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Details Drawer -->
                        <div id="details-<?php echo $order['id']; ?>" class="hidden border-t border-slate-50 bg-slate-50/20 px-6 py-5 transition-all">
                            <div class="max-w-xl space-y-3">
                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Order Items</span>
                                <div class="space-y-2">
                                    <?php foreach ($order['items'] ?? [] as $item): ?>
                                        <div class="flex justify-between items-center text-xs text-slate-600">
                                            <span><?php echo htmlspecialchars($item['food_name']); ?> (Qty <?php echo $item['quantity']; ?>)</span>
                                            <span class="font-bold text-slate-800">$<?php echo number_format($item['subtotal'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="border-t border-slate-100 pt-2 flex justify-between items-center text-xs font-bold text-slate-800">
                                        <span>Grand Total</span>
                                        <span class="text-emerald-600">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Completed Orders (Reorder Button Removed) -->
                <?php foreach ($groupedOrders['completed'] as $order): ?>
                    <div class="order-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden" data-status="completed">
                        <div class="bg-slate-50/30 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span class="text-sm font-bold text-slate-800">Order ID: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="text-xs text-slate-400 font-medium">Delivered <?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="flex items-center space-x-1.5 bg-emerald-50 border border-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                <span>Completed</span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">
                                    <?php echo $order['item_emoji'] ?? '🍽️'; ?>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-slate-900"><?php echo htmlspecialchars($order['item_names'] ?? 'Food Items'); ?></h4>
                                    <p class="text-xs text-slate-400 mt-0.5"><?php echo $order['total_items'] ?? 0; ?> items • Delivered</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="text-left md:text-right pr-4">
                                    <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                    <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <!-- ❌ Reorder Button Removed -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Cancelled Orders (Reorder Button Removed) -->
                <?php foreach ($groupedOrders['cancelled'] as $order): ?>
                    <div class="order-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden" data-status="cancelled">
                        <div class="bg-slate-50/30 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span class="text-sm font-bold text-slate-800">Order ID: #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="text-xs text-slate-400 font-medium">Cancelled <?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="flex items-center space-x-1.5 bg-rose-50 border border-rose-100 text-rose-600 px-3 py-1 rounded-full text-xs font-bold">
                                <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                                <span>Cancelled</span>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div class="flex items-center space-x-4 opacity-70">
                                <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">
                                    <?php echo $order['item_emoji'] ?? '🌮'; ?>
                                </div>
                                <div>
                                    <h4 class="text-base font-extrabold text-slate-900"><?php echo htmlspecialchars($order['item_names'] ?? 'Food Items'); ?></h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Cancelled</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="text-left md:text-right pr-4 opacity-70">
                                    <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                    <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <!-- ❌ Reorder Button Removed -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- LIVE TRACKING MODAL -->
    <div id="live-tracker-modal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-300">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl border border-slate-100 transform scale-95 transition-all duration-300 overflow-hidden" id="tracker-modal-card">
            <div class="bg-slate-900 text-white p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold tracking-tight">Live Delivery Status</h3>
                    <p class="text-xs text-slate-400 mt-1" id="live-order-id">Tracking Order</p>
                </div>
                <button onclick="closeLiveTracking()" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-full interactive-transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 space-y-8">
                <!-- Map Simulation -->
                <div class="relative h-44 bg-emerald-50 rounded-2xl border border-emerald-100 overflow-hidden flex items-center justify-center">
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,#10b98110_1px,transparent_1px),linear-gradient(to_bottom,#10b98110_1px,transparent_1px)] bg-[size:20px_20px]"></div>
                    <div class="z-10 text-center space-y-1">
                        <div class="w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center text-lg mx-auto shadow-md">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-800 tracking-tight mt-1" id="rider-subtext">Rider is on the way</p>
                    </div>
                </div>

                <!-- Milestone Stepper -->
                <div class="relative pl-8 space-y-6">
                    <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-slate-100"></div>
                    <div id="tracker-line-progress" class="absolute left-3 top-2 w-0.5 bg-emerald-500 transition-all duration-700" style="height: 0%"></div>

                    <div class="relative flex items-start gap-4" id="tracking-step-1">
                        <div class="absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white transition-all z-10" id="step-bullet-1">
                            <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Order Placed</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Payment confirmed</p>
                        </div>
                    </div>

                    <div class="relative flex items-start gap-4" id="tracking-step-2">
                        <div class="absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white transition-all z-10" id="step-bullet-2">
                            <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Being Prepared</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kitchen preparing your food</p>
                        </div>
                    </div>

                    <div class="relative flex items-start gap-4" id="tracking-step-3">
                        <div class="absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white transition-all z-10" id="step-bullet-3">
                            <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Out for Delivery</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Rider assigned</p>
                        </div>
                    </div>

                    <div class="relative flex items-start gap-4" id="tracking-step-4">
                        <div class="absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white transition-all z-10" id="step-bullet-4">
                            <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Delivered</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Order completed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="reorder-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800">
        <div class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-cart-arrow-down text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cart Updated</h4>
            <p id="toast-item-label" class="text-sm font-semibold text-slate-100">Items added to cart!</p>
        </div>
    </div>

    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; <?php echo date('Y'); ?> FOODIE INC. All rights reserved.
        </div>
    </footer>

    <script>
        function toggleDetails(elementId) {
            const drawer = document.getElementById(elementId);
            const icon = document.getElementById(`icon-${elementId}`);
            
            if (drawer.classList.contains('hidden')) {
                drawer.classList.remove('hidden');
                if (icon) icon.className = "fa-solid fa-chevron-up text-[10px]";
            } else {
                drawer.classList.add('hidden');
                if (icon) icon.className = "fa-solid fa-chevron-down text-[10px]";
            }
        }

        function filterOrders(status) {
            const tabs = ['all', 'ongoing', 'completed', 'cancelled'];
            tabs.forEach(tab => {
                const button = document.getElementById(`tab-${tab}`);
                if (tab === status) {
                    button.className = "px-5 py-3 border-b-2 border-emerald-500 font-bold text-sm text-emerald-600 transition-colors";
                } else {
                    button.className = "px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors";
                }
            });

            const cards = document.querySelectorAll('.order-card');
            cards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (status === 'all') {
                    card.classList.remove('hidden');
                } else if (cardStatus === status) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        function openLiveTracking(orderId, statusCode) {
            document.getElementById('live-order-id').innerText = `Tracking Order ${orderId}`;
            
            const modal = document.getElementById('live-tracker-modal');
            const card = document.getElementById('tracker-modal-card');
            
            modal.classList.remove('opacity-0', 'pointer-events-none');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');

            const line = document.getElementById('tracker-line-progress');
            const subtext = document.getElementById('rider-subtext');

            if (statusCode === 1) {
                line.style.height = '16%';
                subtext.innerText = "Chef is preparing your order";
                setMilestoneActive(1);
                setMilestonePending(2);
                setMilestonePending(3);
                setMilestonePending(4);
            } else {
                line.style.height = '50%';
                subtext.innerText = "Rider is on the way to you!";
                setMilestoneCompleted(1);
                setMilestoneCompleted(2);
                setMilestoneActive(3);
                setMilestonePending(4);
            }
        }

        function closeLiveTracking() {
            const modal = document.getElementById('live-tracker-modal');
            const card = document.getElementById('tracker-modal-card');
            
            modal.classList.add('opacity-0', 'pointer-events-none');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }

        function setMilestoneCompleted(stepNum) {
            const bullet = document.getElementById(`step-bullet-${stepNum}`);
            bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-emerald-500 bg-emerald-500 text-white z-10 text-[9px] font-extrabold";
            bullet.innerHTML = "<i class='fa-solid fa-check'></i>";
        }

        function setMilestoneActive(stepNum) {
            const bullet = document.getElementById(`step-bullet-${stepNum}`);
            bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-emerald-500 bg-white ring-4 ring-emerald-50 z-10";
            bullet.innerHTML = "<div class='w-2.5 h-2.5 rounded-full bg-emerald-500 live-pulse'></div>";
        }

        function setMilestonePending(stepNum) {
            const bullet = document.getElementById(`step-bullet-${stepNum}`);
            bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white z-10";
            bullet.innerHTML = "<div class='w-2 h-2 rounded-full bg-slate-200'></div>";
        }
    </script>

</body>
</html>