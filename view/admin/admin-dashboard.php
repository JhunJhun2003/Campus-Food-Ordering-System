<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
if (!hasPermission('view_dashboard')) {
    renderAdminPermissionDeniedPage('Access denied', 'dashboard');
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$stats = $adminController->dashboard();
$currentUser = $adminController->getCurrentUser();

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Foodie - Dashboard';
$activePage = 'dashboard';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-start mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?>! Here's your overview.</p>
    </div>
    <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-400"><?php echo date('l, F j, Y'); ?></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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

    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
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
                            <td class="px-6 py-4 font-medium text-gray-900">$<?php echo number_format((float)$order['total_amount'], 2); ?></td>
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
                                <a href="#" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium btn-view-order-details" data-order-id="<?php echo (int) $order['id']; ?>">View</a>
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
</style>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Order Details</h3>
                <p class="text-sm text-slate-500">Complete order information</p>
            </div>
            <button type="button" onclick="closeOrderDetails()" class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="orderDetailsContent" class="p-6"></div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-view-order-details').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const orderId = this.dataset.orderId;
        openOrderDetails(orderId);
    });
});

function openOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');

    content.innerHTML = `
        <div class="text-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-500"></i>
            <p class="text-sm text-slate-400 mt-2">Loading order details...</p>
        </div>
    `;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    fetch('admin-orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_order_details&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.order) {
            renderOrderDetails(data.order);
        } else {
            content.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fa-solid fa-circle-exclamation text-3xl"></i>
                    <p class="text-sm mt-2">${data.message || 'Failed to load order details.'}</p>
                </div>
            `;
        }
    })
    .catch(() => {
        content.innerHTML = `
            <div class="text-center py-8 text-red-500">
                <i class="fa-solid fa-circle-exclamation text-3xl"></i>
                <p class="text-sm mt-2">Error loading order details</p>
            </div>
        `;
    });
}

function renderOrderDetails(order) {
    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'accepted': 'bg-blue-100 text-blue-800',
        'preparing': 'bg-purple-100 text-purple-800',
        'ready': 'bg-cyan-100 text-cyan-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };

    const statusColor = statusColors[(order.status_name || '').toLowerCase()] || 'bg-gray-100 text-gray-800';
    const isCOD = order.is_cod || order.payment_method === 'Cash on Delivery';

    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <div class="flex justify-between items-center py-2 border-b border-slate-100 last:border-0">
                    <span>${item.food_name} (Qty: ${item.quantity})</span>
                    <span class="font-medium">$${parseFloat(item.subtotal).toFixed(2)}</span>
                </div>
            `;
        });
    } else {
        itemsHtml = '<p class="text-sm text-slate-400">No items found</p>';
    }

    let paymentHtml = '';
    if (isCOD) {
        paymentHtml = `
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Payment Method</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fa-solid fa-truck mr-1.5"></i> Cash on Delivery
                </span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Payment Status</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="font-medium text-slate-500">Account Details</span>
                <span class="text-slate-400 text-sm italic">Not applicable for Cash on Delivery</span>
            </div>
        `;
    } else {
        paymentHtml = `
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Payment Method</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fa-solid fa-credit-card mr-1.5"></i> ${order.payment_method || 'N/A'}
                </span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Payment Status</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${order.payment_status ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1) : 'Pending'}
                </span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Account Name</span>
                <span class="font-medium">${order.account_name || 'N/A'}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="font-medium text-slate-500">Account Number</span>
                <span class="font-mono text-sm">${order.account_number || 'N/A'}</span>
            </div>
        `;
    }

    const content = document.getElementById('orderDetailsContent');
    content.innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColor}">${order.status_name || 'N/A'}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Order ID</span>
                        <span class="font-bold">#${order.id}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Customer</span>
                        <span>${order.customer_name || 'Unknown'}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Phone</span>
                        <span>${order.customer_phone || 'N/A'}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="font-medium text-slate-500">Address</span>
                        <span>${order.delivery_address || 'N/A'}</span>
                    </div>
                </div>
                <div>${paymentHtml}</div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Order Items</h3>
                <div class="bg-slate-50 rounded-lg p-3">${itemsHtml}</div>
            </div>

            <div class="border-t border-slate-100 pt-3 flex justify-between font-bold text-slate-900">
                <span>Total Amount</span>
                <span class="text-emerald-600">$${parseFloat(order.total_amount).toFixed(2)}</span>
            </div>
        </div>
    `;
}

function closeOrderDetails() {
    const modal = document.getElementById('orderDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderDetails();
    }
});

const params = new URLSearchParams(window.location.search);
const initialOrderId = params.get('order_id');
if (initialOrderId) {
    openOrderDetails(initialOrderId);
}
</script>
</main>
</body>
</html>