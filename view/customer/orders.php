<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('view_orders');

use App\User\Presentation\Http\Controllers\UserController;
use App\Order\Presentation\Http\Controllers\OrderController;
use App\Cart\Presentation\Http\Controllers\CartController;

$userController = new UserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// Get cart item count
$itemCount = 0;
try {
    $cartController = new CartController();
    $itemCount = $cartController->getItemCount($userId);
} catch (\Exception $e) {
    $itemCount = 0;
}

// Get orders
$orderController = new OrderController();
$orders = $orderController->getUserOrders($userId);
$statuses = $orderController->getStatuses();

// Create status mapping for quick lookup
$statusMap = [];
foreach ($statuses as $status) {
    $statusMap[$status['id']] = $status['status_name'];
}

// Group orders by status - using array data
$groupedOrders = ['ongoing' => [], 'completed' => [], 'cancelled' => []];
foreach ($orders as $order) {
    // Access array values directly
    $statusId = $order['status_id'] ?? 1;
    $statusName = $statusMap[$statusId] ?? 'pending';
    
    if (in_array($statusName, ['pending', 'accepted', 'preparing', 'ready'])) {
        $groupedOrders['ongoing'][] = $order;
    } elseif ($statusName === 'completed') {
        $groupedOrders['completed'][] = $order;
    } elseif ($statusName === 'cancelled') {
        $groupedOrders['cancelled'][] = $order;
    }
}

// Status colors for badges
$statusColors = [
    'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    'accepted' => 'bg-blue-50 border-blue-200 text-blue-700',
    'preparing' => 'bg-purple-50 border-purple-200 text-purple-700',
    'ready' => 'bg-cyan-50 border-cyan-200 text-cyan-700',
    'completed' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
    'cancelled' => 'bg-rose-50 border-rose-100 text-rose-600'
];

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - My Orders';
$activePage = 'orders';

include __DIR__ . '/includes/header.php';
?>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- Welcome Header -->
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
    <div class="flex items-center space-x-2 border-b border-slate-100 pb-px mb-8 overflow-x-auto whitespace-nowrap" id="filterTabs">
        <button class="tab-btn px-5 py-3 border-b-2 border-emerald-500 font-bold text-sm text-emerald-600 hover:text-emerald-500 transition-colors" data-status="all">All Orders</button>
        <button class="tab-btn px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors" data-status="ongoing">Ongoing</button>
        <button class="tab-btn px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors" data-status="completed">Completed</button>
        <button class="tab-btn px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors" data-status="cancelled">Cancelled</button>
    </div>

    <!-- Orders List -->
    <div class="space-y-6" id="orders-list-container">
        
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="rounded-2xl p-12 text-center bg-white border-2 border-dashed border-slate-200">
                <div class="text-slate-300 text-5xl mb-4"><i class="fa-regular fa-receipt"></i></div>
                <h3 class="text-lg font-bold text-slate-800">No orders yet</h3>
                <p class="text-slate-400 text-sm mt-1 mb-6">Start ordering delicious food from our menu!</p>
                <a href="dashboard.php" class="inline-flex items-center space-x-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-3 rounded-lg text-sm transition-all">
                    <span>Browse Menu</span>
                </a>
            </div>
            
        <?php else: ?>
            
            <!-- Ongoing Orders -->
            <?php foreach ($groupedOrders['ongoing'] as $order): ?>
                <?php 
                    $statusId = $order['status_id'] ?? 1;
                    $statusName = $statusMap[$statusId] ?? 'pending';
                    $statusColor = $statusColors[$statusName] ?? 'bg-slate-50 border-slate-200 text-slate-700';
                    $isPreparing = $statusName === 'preparing';
                    $isReady = $statusName === 'ready';
                    $pulseColor = $isPreparing ? '#F59E0B' : ($isReady ? '#10B981' : '#3B82F6');
                    $orderId = $order['id'] ?? 0;
                ?>
                <div class="order-card bg-white border border-slate-150 rounded-2xl shadow-sm shadow-slate-100/60 overflow-hidden" data-status="ongoing">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="text-sm font-bold text-slate-900">Order ID: #<?php echo sprintf('%06d', (int) $orderId); ?></span>
                            <span class="text-xs text-slate-400 font-medium">Placed <?php echo date('M j, Y, h:i A', strtotime($order['order_date'] ?? 'now')); ?></span>
                        </div>
                        <div class="flex items-center space-x-2 <?php echo $statusColor; ?> px-3.5 py-1.5 rounded-full text-xs font-bold">
                            <span class="w-2 h-2 rounded-full live-pulse" style="background-color: <?php echo $pulseColor; ?>"></span>
                            <span><?php echo ucfirst($statusName); ?></span>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">🍔</div>
                            <div>
                                <h4 class="text-base font-extrabold text-slate-900">Order #<?php echo sprintf('%06d', (int) $orderId); ?></h4>
                                <p class="text-xs text-slate-400 mt-0.5"><?php echo $order['total_items'] ?? 0; ?> items</p>
                                <button onclick="toggleDetails('details-<?php echo $orderId; ?>')" class="text-xs font-bold text-emerald-500 hover:text-emerald-600 mt-2 flex items-center gap-1 focus:outline-none">
                                    <span>View details</span>
                                    <i class="fa-solid fa-chevron-down text-[10px]" id="icon-details-<?php echo $orderId; ?>"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="text-left md:text-right pr-4">
                                <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format((float) ($order['total_amount'] ?? 0), 2); ?></span>
                            </div>
                            <?php if ($isReady || $isPreparing): ?>
                                <button onclick="openLiveTracking('#<?php echo sprintf('%06d', (int) $orderId); ?>', <?php echo $isReady ? 2 : 1; ?>)" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-5 py-3 rounded-xl text-xs shadow-md shadow-emerald-500/15 hover:shadow-emerald-500/30 transition-all flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-location-crosshairs"></i><span>Track Live</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Details Drawer -->
                    <div id="details-<?php echo $orderId; ?>" class="hidden border-t border-slate-50 bg-slate-50/20 px-6 py-5 transition-all">
                        <div class="max-w-xl space-y-3">
                            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block">Order Items</span>
                            <div class="space-y-2">
                                <?php 
                                $orderItems = $orderController->getOrderItems($orderId);
                                foreach ($orderItems as $item): 
                                ?>
                                    <div class="flex justify-between items-center text-xs text-slate-600">
                                        <span><?php echo htmlspecialchars($item['food_name']); ?> (Qty <?php echo $item['quantity']; ?>)</span>
                                        <span class="font-bold text-slate-800">$<?php echo number_format((float) $item['subtotal'], 2); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="border-t border-slate-100 pt-2 flex justify-between items-center text-xs font-bold text-slate-800">
                                    <span>Grand Total</span>
                                    <span class="text-emerald-600">$<?php echo number_format((float) ($order['total_amount'] ?? 0), 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Completed Orders -->
            <?php foreach ($groupedOrders['completed'] as $order): ?>
                <?php 
                    $statusId = $order['status_id'] ?? 5;
                    $statusName = $statusMap[$statusId] ?? 'completed';
                    $orderId = $order['id'] ?? 0;
                ?>
                <div class="order-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden" data-status="completed">
                    <div class="bg-slate-50/30 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="text-sm font-bold text-slate-800">Order ID: #<?php echo sprintf('%06d', (int) $orderId); ?></span>
                            <span class="text-xs text-slate-400 font-medium">Delivered <?php echo date('M j, Y', strtotime($order['order_date'] ?? 'now')); ?></span>
                        </div>
                        <div class="flex items-center space-x-1.5 bg-emerald-50 border border-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">
                            <i class="fa-solid fa-circle-check text-[10px]"></i><span>Completed</span>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">🍽️</div>
                            <div>
                                <h4 class="text-base font-extrabold text-slate-900">Order #<?php echo sprintf('%06d', (int) $orderId); ?></h4>
                                <p class="text-xs text-slate-400 mt-0.5"><?php echo $order['total_items'] ?? 0; ?> items • Delivered</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="text-left md:text-right pr-4">
                                <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format((float) ($order['total_amount'] ?? 0), 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Cancelled Orders -->
            <?php foreach ($groupedOrders['cancelled'] as $order): ?>
                <?php 
                    $statusId = $order['status_id'] ?? 6;
                    $statusName = $statusMap[$statusId] ?? 'cancelled';
                    $orderId = $order['id'] ?? 0;
                ?>
                <div class="order-card bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden" data-status="cancelled">
                    <div class="bg-slate-50/30 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="text-sm font-bold text-slate-800">Order ID: #<?php echo sprintf('%06d', (int) $orderId); ?></span>
                            <span class="text-xs text-slate-400 font-medium">Cancelled <?php echo date('M j, Y', strtotime($order['order_date'] ?? 'now')); ?></span>
                        </div>
                        <div class="flex items-center space-x-1.5 bg-rose-50 border border-rose-100 text-rose-600 px-3 py-1 rounded-full text-xs font-bold">
                            <i class="fa-solid fa-circle-xmark text-[10px]"></i><span>Cancelled</span>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center space-x-4 opacity-70">
                            <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl">🌮</div>
                            <div>
                                <h4 class="text-base font-extrabold text-slate-900">Order #<?php echo sprintf('%06d', (int) $orderId); ?></h4>
                                <p class="text-xs text-slate-400 mt-0.5">Cancelled</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="text-left md:text-right pr-4 opacity-70">
                                <span class="text-xs text-slate-400 block font-semibold uppercase tracking-wider">Total</span>
                                <span class="text-xl font-extrabold text-slate-900">$<?php echo number_format((float) ($order['total_amount'] ?? 0), 2); ?></span>
                            </div>
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
            <button onclick="closeLiveTracking()" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-full transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6 space-y-8">
            <div class="relative h-44 bg-emerald-50 rounded-2xl border border-emerald-100 overflow-hidden flex items-center justify-center">
                <div class="absolute inset-0 bg-[linear-gradient(to_right,#10b98110_1px,transparent_1px),linear-gradient(to_bottom,#10b98110_1px,transparent_1px)] bg-[size:20px_20px]"></div>
                <div class="z-10 text-center space-y-1">
                    <div class="w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center text-lg mx-auto shadow-md">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-800 tracking-tight mt-1" id="rider-subtext">Rider is on the way</p>
                </div>
            </div>

            <div class="relative pl-8 space-y-6">
                <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-slate-100"></div>
                <div id="tracker-line-progress" class="absolute left-3 top-2 w-0.5 bg-emerald-500 transition-all duration-700" style="height: 0%"></div>

                <?php 
                $steps = [
                    ['id' => 1, 'label' => 'Order Placed', 'desc' => 'Payment confirmed'],
                    ['id' => 2, 'label' => 'Being Prepared', 'desc' => 'Kitchen preparing your food'],
                    ['id' => 3, 'label' => 'Out for Delivery', 'desc' => 'Rider assigned'],
                    ['id' => 4, 'label' => 'Delivered', 'desc' => 'Order completed']
                ];
                foreach ($steps as $step): 
                ?>
                <div class="relative flex items-start gap-4" id="tracking-step-<?php echo $step['id']; ?>">
                    <div class="absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white transition-all z-10" id="step-bullet-<?php echo $step['id']; ?>">
                        <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900"><?php echo $step['label']; ?></h4>
                        <p class="text-[11px] text-slate-500 mt-0.5"><?php echo $step['desc']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.interactive-transition { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.order-card { animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.live-pulse { animation: pulse-ring 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes slideIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse-ring { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(1.15); } }
</style>

<script>
// ============================================
// TOGGLE DETAILS
// ============================================
function toggleDetails(elementId) {
    const drawer = document.getElementById(elementId);
    const icon = document.getElementById(`icon-${elementId}`);
    if (drawer) {
        if (drawer.classList.contains('hidden')) {
            drawer.classList.remove('hidden');
            if (icon) icon.className = "fa-solid fa-chevron-up text-[10px]";
        } else {
            drawer.classList.add('hidden');
            if (icon) icon.className = "fa-solid fa-chevron-down text-[10px]";
        }
    }
}

// ============================================
// FILTER ORDERS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.getElementById('filterTabs');
    const orderCards = document.querySelectorAll('.order-card');
    
    if (filterTabs) {
        filterTabs.addEventListener('click', function(e) {
            const tab = e.target.closest('.tab-btn');
            if (!tab) return;
            
            const status = tab.dataset.status;
            
            // Update active tab styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors';
            });
            tab.className = 'tab-btn px-5 py-3 border-b-2 border-emerald-500 font-bold text-sm text-emerald-600 hover:text-emerald-500 transition-colors';
            
            // Filter orders
            orderCards.forEach(card => {
                const cardStatus = card.dataset.status;
                if (status === 'all' || cardStatus === status) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    }
});

// ============================================
// LIVE TRACKING
// ============================================
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
        [2, 3, 4].forEach(step => setMilestonePending(step));
    } else {
        line.style.height = '50%';
        subtext.innerText = "Rider is on the way to you!";
        [1, 2].forEach(step => setMilestoneCompleted(step));
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

<?php include __DIR__ . '/includes/footer.php'; ?>