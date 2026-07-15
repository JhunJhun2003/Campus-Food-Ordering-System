<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php'; 
require_once __DIR__ . '/../../inc/access_control_helper.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requireEmailVerification();
// ✅ Check maintenance mode
checkMaintenanceRedirect();
// ✅ Redirect admin/staff away from customer dashboard
redirectAdminStaffFromCustomer();
requirePermission('view_orders');

use App\User\Presentation\Http\Controllers\UserController;

$userController = getUserController();
$currentUser = $userController->getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$orderController = getOrderController();
$cartController = getCartController();

// Get cart item count
$itemCount = 0;
try {
    $itemCount = $cartController->getItemCount($userId);
} catch (\Exception $e) {
    $itemCount = 0;
}

// ✅ Get ALL orders for the user
$orders = $orderController->getUserOrders($userId);

// ✅ Get statuses from order_statuses table
$statuses = $orderController->getStatuses();

// Create status mapping
$statusMap = [];
$statusColors = [];
$statusIcons = [];

foreach ($statuses as $status) {
    $id = $status['id'];
    $name = strtolower($status['status_name']);
    $statusMap[$id] = $name;
    
    $statusColors[$name] = match($name) {
        'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
        'accepted' => 'bg-blue-50 border-blue-200 text-blue-700',
        'preparing' => 'bg-purple-50 border-purple-200 text-purple-700',
        'ready' => 'bg-cyan-50 border-cyan-200 text-cyan-700',
        'completed' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
        'cancelled' => 'bg-rose-50 border-rose-100 text-rose-600',
        default => 'bg-slate-50 border-slate-200 text-slate-700'
    };
    
    $statusIcons[$name] = match($name) {
        'pending' => 'fa-solid fa-clock',
        'accepted' => 'fa-solid fa-check-circle',
        'preparing' => 'fa-solid fa-utensils',
        'ready' => 'fa-solid fa-box-open',
        'completed' => 'fa-solid fa-circle-check',
        'cancelled' => 'fa-solid fa-circle-xmark',
        default => 'fa-solid fa-circle'
    };
}

// ✅ Build filter tabs dynamically
$filterTabs = [];
$filterTabs[] = [
    'key' => 'all',
    'label' => 'All Orders',
    'icon' => 'fa-solid fa-list',
    'status_id' => null
];

foreach ($statuses as $status) {
    $name = strtolower($status['status_name']);
    $filterTabs[] = [
        'key' => $name,
        'label' => ucfirst($status['status_name']),
        'icon' => $statusIcons[$name] ?? 'fa-solid fa-circle',
        'status_id' => $status['id']
    ];
}

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
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wide">Total</span>
                <span class="text-lg font-extrabold text-emerald-600"><?php echo count($orders); ?> Orders</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-2 border-b border-slate-100 pb-px overflow-x-auto whitespace-nowrap" id="filterTabs">
            <?php foreach ($filterTabs as $index => $tab): 
                $isActive = $index === 0 ? 'border-emerald-500 font-bold text-emerald-600' : 'border-transparent font-semibold text-slate-500';
            ?>
                <button class="tab-btn px-5 py-3 border-b-2 <?php echo $isActive; ?> hover:text-emerald-500 transition-colors text-sm" 
                        data-status="<?php echo $tab['key']; ?>"
                        data-status-id="<?php echo $tab['status_id']; ?>">
                    <i class="<?php echo $tab['icon']; ?> mr-1.5"></i>
                    <?php echo $tab['label']; ?>
                    <?php if ($tab['key'] !== 'all'): ?>
                        <span class="ml-1 text-xs opacity-60">
                            (<?php 
                                $count = 0;
                                foreach ($orders as $order) {
                                    if (strtolower($order['status_name'] ?? '') === $tab['key']) {
                                        $count++;
                                    }
                                }
                                echo $count;
                            ?>)
                        </span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- ✅ SEARCH BAR -->
        <div class="relative flex-shrink-0 w-full sm:w-64">
            <input type="text" 
                   id="searchOrders" 
                   placeholder="Search by Order ID or Item..." 
                   class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white/80">
            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <button id="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 hidden">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
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
            
            <?php foreach ($orders as $order): ?>
                <?php 
                    $statusId = $order['status_id'] ?? 1;
                    $orderId = $order['id'] ?? 0;
                    $statusName = $statusMap[$statusId] ?? 'pending';
                    $statusColor = $statusColors[$statusName] ?? 'bg-slate-50 border-slate-200 text-slate-700';
                    $statusIcon = $statusIcons[$statusName] ?? 'fa-solid fa-circle';
                    $isPreparing = $statusName === 'preparing';
                    $isReady = $statusName === 'ready';
                    $isPending = $statusName === 'pending';
                    $pulseColor = $isPreparing ? '#F59E0B' : ($isReady ? '#10B981' : ($isPending ? '#3B82F6' : '#64748B'));
                    
                    // Check if order can request refund
                    $canRefund = in_array($statusId, [1, 2]); // pending or confirmed
                    $hasPendingRefund = false;
                    
                    // Check if order has pending refund
                    try {
                        $db = \Inc\Database::getConnection();
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM refunds WHERE order_id = :order_id AND refund_status_id = 1");
                        $stmt->execute([':order_id' => $orderId]);
                        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $hasPendingRefund = (int) ($result['count'] ?? 0) > 0;
                    } catch (\Exception $e) {
                        $hasPendingRefund = false;
                    }
                ?>
                <div class="order-card bg-white border border-slate-150 rounded-2xl shadow-sm shadow-slate-100/60 overflow-hidden" data-status="<?php echo $statusName; ?>">
                    <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <span class="text-sm font-bold text-slate-900 order-id-text">Order ID: #<?php echo sprintf('%06d', (int) $orderId); ?></span>
                            <span class="text-xs text-slate-400 font-medium">Placed <?php echo date('M j, Y, h:i A', strtotime($order['order_date'] ?? 'now')); ?></span>
                        </div>
                        <div class="flex items-center space-x-2 <?php echo $statusColor; ?> px-3.5 py-1.5 rounded-full text-xs font-bold">
                            <i class="<?php echo $statusIcon; ?> text-[10px]"></i>
                            <span class="w-2 h-2 rounded-full live-pulse" style="background-color: <?php echo $pulseColor; ?>"></span>
                            <span><?php echo ucfirst($statusName); ?></span>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 text-3xl"><?php echo $order['item_emoji'] ?? '🍔'; ?></div>
                            <div>
                                <h4 class="text-base font-extrabold text-slate-900">Order #<?php echo sprintf('%06d', (int) $orderId); ?></h4>
                                <p class="text-xs text-slate-400 mt-0.5"><?php echo $order['total_items'] ?? 0; ?> items</p>
                                <p class="text-xs text-slate-500 mt-0.5 item-name-text"><?php echo htmlspecialchars($order['item_names'] ?? ''); ?></p>
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
                            <a href="/Campus-Food-Ordering-System/Public/receipt.php?id=<?php echo $orderId; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Print Receipt">
    <i class="fa-solid fa-print"></i>
</a>
                            <?php if ($canRefund && !$hasPendingRefund): ?>
                                <button class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md shadow-amber-500/15 hover:shadow-amber-500/30 transition-all flex items-center justify-center space-x-2 btn-refund-order" data-order-id="<?php echo $orderId; ?>">
                                    <i class="fa-solid fa-rotate-left"></i><span>Request Refund</span>
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

        <?php endif; ?>
    </div>
</main>

<!-- ============================================ -->
<!-- LIVE TRACKING MODAL -->
<!-- ============================================ -->
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

<!-- ============================================ -->
<!-- REFUND MODAL -->
<!-- ============================================ -->
<div id="refundModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl transform scale-95 transition-all duration-300" id="refundModalCard">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Request Refund</h2>
            <button onclick="closeRefundModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <form id="refundForm" class="space-y-4">
            <input type="hidden" name="order_id" id="refundOrderId">
            
            <div>
                <label for="refundReason" class="block text-sm font-medium text-slate-700 mb-1">Reason for Refund</label>
                <textarea id="refundReason" name="reason" rows="4" 
                          class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-emerald-500 text-sm placeholder-slate-400"
                          placeholder="Please explain why you want to refund this order..."></textarea>
                <p class="text-xs text-slate-400 mt-1">Minimum 5 characters, maximum 500 characters</p>
            </div>
            
            <div class="flex space-x-3 pt-2">
                <button type="button" onclick="closeRefundModal()" 
                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Submit Refund Request
                </button>
            </div>
        </form>
        
        <div id="refundResponse" class="hidden mt-4 p-4 rounded-lg"></div>
    </div>
</div>

<style>
.interactive-transition { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.order-card { animation: slideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.live-pulse { animation: pulse-ring 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes slideIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse-ring { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(1.15); } }

.order-card {
    display: block !important;
}
.order-card.hidden {
    display: none !important;
}
</style>

<script>
// ============================================
// TOGGLE DETAILS
// ============================================
function toggleDetails(elementId) {
    const drawer = document.getElementById(elementId);
    const icon = document.getElementById('icon-' + elementId);
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
// FILTER ORDERS WITH SEARCH
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.getElementById('filterTabs');
    const searchInput = document.getElementById('searchOrders');
    const clearBtn = document.getElementById('clearSearch');
    
    // Function to apply both filter and search
    function applyFilters(status, searchTerm) {
        const search = (searchTerm || '').toLowerCase().trim();
        let visibleCount = 0;
        
        document.querySelectorAll('.order-card').forEach(card => {
            const cardStatus = card.dataset.status;
            
            // Get text content from the card
            const orderIdElement = card.querySelector('.order-id-text');
            const itemNameElement = card.querySelector('.item-name-text');
            
            const orderId = orderIdElement ? orderIdElement.textContent : '';
            const itemName = itemNameElement ? itemNameElement.textContent : '';
            const orderText = (orderId + ' ' + itemName).toLowerCase();
            
            const matchesFilter = status === 'all' || cardStatus === status;
            const matchesSearch = search === '' || orderText.includes(search);
            
            if (matchesFilter && matchesSearch) {
                card.style.display = 'block';
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.classList.add('hidden');
            }
        });
        
        // Show/hide no results message
        const container = document.getElementById('orders-list-container');
        let noResults = document.getElementById('no-results-message');
        
        if (noResults) {
            noResults.remove();
        }
        
        if (visibleCount === 0 && document.querySelectorAll('.order-card').length > 0) {
            noResults = document.createElement('div');
            noResults.id = 'no-results-message';
            noResults.className = 'col-span-full text-center py-16 bg-white rounded-2xl border border-slate-100 shadow-sm';
            noResults.innerHTML = `
                <div class="text-slate-300 text-5xl mb-4"><i class="fa-regular fa-search"></i></div>
                <h3 class="text-lg font-bold text-slate-800">No matching orders</h3>
                <p class="text-sm text-slate-500 mt-1">Try adjusting your search or filter criteria.</p>
            `;
            container.appendChild(noResults);
        }
    }
    
    // Filter tab click handler
    if (filterTabs) {
        filterTabs.addEventListener('click', function(e) {
            const tab = e.target.closest('.tab-btn');
            if (!tab) return;
            
            const status = tab.dataset.status;
            const searchTerm = searchInput ? searchInput.value : '';
            
            // Update active tab styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.className = 'tab-btn px-5 py-3 border-b-2 border-transparent font-semibold text-sm text-slate-500 hover:text-emerald-500 transition-colors';
            });
            tab.className = 'tab-btn px-5 py-3 border-b-2 border-emerald-500 font-bold text-sm text-emerald-600 hover:text-emerald-500 transition-colors';
            
            applyFilters(status, searchTerm);
        });
    }
    
    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value;
            const activeTab = document.querySelector('.tab-btn.border-emerald-500');
            const status = activeTab ? activeTab.dataset.status : 'all';
            
            // Show/hide clear button
            if (clearBtn) {
                if (searchTerm.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }
            
            applyFilters(status, searchTerm);
        });
    }
    
    // Clear search button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.classList.add('hidden');
            const activeTab = document.querySelector('.tab-btn.border-emerald-500');
            const status = activeTab ? activeTab.dataset.status : 'all';
            applyFilters(status, '');
            searchInput.focus();
        });
    }
    
    // ✅ Initial load - show all orders
    applyFilters('all', '');
});

// ============================================
// LIVE TRACKING
// ============================================
function openLiveTracking(orderId, statusCode) {
    document.getElementById('live-order-id').innerText = 'Tracking Order ' + orderId;
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
    const bullet = document.getElementById('step-bullet-' + stepNum);
    if (bullet) {
        bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-emerald-500 bg-emerald-500 text-white z-10 text-[9px] font-extrabold";
        bullet.innerHTML = "<i class='fa-solid fa-check'></i>";
    }
}

function setMilestoneActive(stepNum) {
    const bullet = document.getElementById('step-bullet-' + stepNum);
    if (bullet) {
        bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-emerald-500 bg-white ring-4 ring-emerald-50 z-10";
        bullet.innerHTML = "<div class='w-2.5 h-2.5 rounded-full bg-emerald-500 live-pulse'></div>";
    }
}

function setMilestonePending(stepNum) {
    const bullet = document.getElementById('step-bullet-' + stepNum);
    if (bullet) {
        bullet.className = "absolute -left-8 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-200 bg-white z-10";
        bullet.innerHTML = "<div class='w-2 h-2 rounded-full bg-slate-200'></div>";
    }
}

// ============================================
// REFUND FUNCTIONALITY
// ============================================

// Refund button click handler
document.querySelectorAll('.btn-refund-order').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const orderId = this.dataset.orderId;
        openRefundModal(orderId);
    });
});

// Open refund modal
function openRefundModal(orderId) {
    const modal = document.getElementById('refundModal');
    const card = document.getElementById('refundModalCard');
    document.getElementById('refundOrderId').value = orderId;
    document.getElementById('refundReason').value = '';
    document.getElementById('refundResponse').classList.add('hidden');
    modal.classList.remove('hidden');
    card.classList.remove('scale-95');
    card.classList.add('scale-100');
    document.body.style.overflow = 'hidden';
}

// Close refund modal
function closeRefundModal() {
    const modal = document.getElementById('refundModal');
    const card = document.getElementById('refundModalCard');
    modal.classList.add('hidden');
    card.classList.add('scale-95');
    card.classList.remove('scale-100');
    document.body.style.overflow = '';
}

// Handle refund form submission
document.getElementById('refundForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const orderId = document.getElementById('refundOrderId').value;
    const reason = document.getElementById('refundReason').value;
    const submitBtn = this.querySelector('button[type="submit"]');
    const responseDiv = document.getElementById('refundResponse');
    
    // Validate
    if (!reason || reason.length < 5) {
        responseDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm';
        responseDiv.innerHTML = 'Please provide a reason (minimum 5 characters).';
        responseDiv.classList.remove('hidden');
        return;
    }
    
    if (reason.length > 500) {
        responseDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm';
        responseDiv.innerHTML = 'Reason is too long (maximum 500 characters).';
        responseDiv.classList.remove('hidden');
        return;
    }
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...';
    responseDiv.classList.add('hidden');
    
    fetch('/Campus-Food-Ordering-System/Public/api/refund/request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            responseDiv.className = 'mt-4 p-4 rounded-lg bg-green-50 text-green-700 text-sm';
            responseDiv.innerHTML = `
                <i class="fa-solid fa-check-circle mr-2"></i>
                ${data.message}
                ${data.data?.refund_id ? `<br><span class="text-xs">Refund ID: #${data.data.refund_id}</span>` : ''}
            `;
            responseDiv.classList.remove('hidden');
            
            // Close modal after 3 seconds and reload
            setTimeout(() => {
                closeRefundModal();
                location.reload();
            }, 3000);
        } else {
            responseDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm';
            responseDiv.innerHTML = `
                <i class="fa-solid fa-exclamation-circle mr-2"></i>
                ${data.message}
                ${data.errors ? `<br><span class="text-xs">${Object.values(data.errors).join(' ')}</span>` : ''}
            `;
            responseDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Refund Request';
        }
    })
    .catch(error => {
        responseDiv.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-700 text-sm';
        responseDiv.innerHTML = 'An error occurred. Please try again.';
        responseDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Refund Request';
        console.error('Error:', error);
    });
});

// Close refund modal on overlay click
document.getElementById('refundModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRefundModal();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>