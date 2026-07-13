<?php
declare(strict_types=1);

session_start();

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/order_helpers.php';

// ✅ Check maintenance mode - staff cannot access during maintenance
checkMaintenanceRedirect();
if (isAdmin()) {
    $_SESSION['error'] = 'Staff pages are for staff members only.';
    header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
    exit();
}
requireStaffAuth();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Staff';
$userRole = $_SESSION['user_role'] ?? 'staff';

$permissions = getStaffPermissions($userId);

// Check if user can view orders
if (!$permissions['viewOrders']) {
    $_SESSION['error'] = "You do not have permission to view orders.";
    header('Location: /Campus-Food-Ordering-System/view/staff/staff-dashboard.php');
    exit();
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use the helper function instead of creating a new instance
$orderController = getOrderController();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        // Check permission
        if (!$permissions['updateOrderStatus']) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update order status.']);
            exit();
        }
        
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $statusId = (int) ($_POST['status_id'] ?? 0);

        if ($orderId <= 0 || $statusId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order or status.']);
            exit();
        }

        echo json_encode($orderController->updateStatus($orderId, $statusId));
        exit();
    }
    
    if ($action === 'get_order_details') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
            exit();
        }
        
        try {
            $orderRepository = new \App\Order\Infrastructure\Repositories\OrderRepository();
            
            // ✅ Use the new method that includes payment details
            $order = $orderRepository->findByIdWithDetails($orderId);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found.']);
                exit();
            }
            
            $items = $orderRepository->getOrderItems($orderId);
            
            // Get status name from the order data
            $statusName = $order['status_name'] ?? 'pending';
            
            // ✅ Get payment details
            $paymentMethod = $order['payment_method_name'] ?? 'Cash on Delivery';
            $accountName = $order['payment_account_name'] ?? '';
            $accountNumber = $order['payment_account_number'] ?? '';
            $transactionNo = $order['transaction_no'] ?? '';
            $paymentStatus = $order['payment_status_name'] ?? '';
            $isCOD = $paymentMethod === 'Cash on Delivery';
            
            echo json_encode([
                'success' => true,
                'order' => [
                    'id' => $order['id'],
                    'order_date' => $order['order_date'],
                    'customer_name' => $order['customer_name'] ?? $order['customer_name_from_user'] ?? 'Unknown',
                    'customer_phone' => $order['customer_phone'] ?? $order['customer_phone_from_user'] ?? 'N/A',
                    'delivery_address' => $order['delivery_address'] ?? 'N/A',
                    'payment_method' => $paymentMethod,
                    'account_name' => $accountName,
                    'account_number' => $accountNumber,
                    'transaction_no' => $transactionNo,
                    'payment_status' => $paymentStatus,
                    'is_cod' => $isCOD,
                    'total_amount' => $order['total_amount'],
                    'items' => $items,
                    'status_id' => $order['status_id'],
                    'status_name' => $statusName,
                    // ✅ ADD THIS LINE:
                    'transaction_image' => $order['transaction_image'] ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

$orders = $orderController->index();
$statuses = $orderController->getStatuses();

// ============================================
// 3. VIEW RENDER
// ============================================

$pageTitle = 'Staff Orders - Foodie';
$activePage = 'orders';
$customCss = 'css/staff-orders.css';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="flex-1 p-8 overflow-y-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Orders</h1>
            <p class="text-sm text-slate-500">Manage all customer orders</p>
        </div>
        <div class="flex items-center space-x-3">
            <select class="px-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white" id="statusFilter">
                <option value="">All Status</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status['id']; ?>"><?php echo ucfirst($status['status_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium" onclick="location.reload()">
                <i class="fa-solid fa-rotate"></i>
                <span>Refresh</span>
            </button>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <!-- Search Bar -->
        <div class="p-5 flex items-center justify-between border-b border-slate-50">
            <div class="relative w-full max-w-xl">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                </span>
                <input type="text" placeholder="Search orders by ID or customer..." class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-slate-400" id="searchInput">
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-6 text-center w-16">#</th>
                        <th class="py-3 px-6">Order ID</th>
                        <th class="py-3 px-6">Customer</th>
                        <th class="py-3 px-6">Phone</th>
                        <th class="py-3 px-6">Total</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6">Date</th>
                        <th class="py-3 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-regular fa-receipt text-4xl block mb-3"></i>
                                <p class="text-sm font-medium">No orders found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-6 text-center text-slate-400 text-xs"><?php echo $counter++; ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900">#<?php echo $order->getId(); ?></td>
                                <td class="py-4 px-6 text-slate-600"><?php echo htmlspecialchars($order->getCustomerName() ?? 'Unknown'); ?></td>
                                <td class="py-4 px-6 text-slate-600"><?php echo htmlspecialchars($order->getCustomerPhone() ?? 'N/A'); ?></td>
                                <td class="py-4 px-6 font-medium text-slate-900">$<?php echo number_format($order->getTotalAmount(), 2); ?></td>
                                <td class="py-4 px-6">
                                    <?php
                                        $statusName = '';
                                        foreach ($statuses as $status) {
                                            if ($status['id'] == $order->getStatusId()) {
                                                $statusName = $status['status_name'];
                                                break;
                                            }
                                        }
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'accepted' => 'bg-blue-100 text-blue-800',
                                            'preparing' => 'bg-purple-100 text-purple-800',
                                            'ready' => 'bg-cyan-100 text-cyan-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $colorClass = $statusColors[$statusName] ?? 'bg-slate-100 text-slate-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $colorClass; ?>">
                                        <?php echo ucfirst($statusName); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-400 text-xs">
                                    <?php echo $order->getOrderDate()->format('M d, Y h:i A'); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center space-x-2">
                                        <!-- View Details -->
                                        <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium btn-view-details" 
                                                data-order-id="<?php echo $order->getId(); ?>" title="View Order Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        
                                        <!-- Update Status -->
                                        <?php if ($permissions['updateOrderStatus']): ?>
                                        <div class="status-action flex items-center gap-2 p-1 border border-slate-200 rounded-lg bg-slate-50">
                                            <select class="status-select border-0 bg-transparent text-slate-600 text-sm font-medium outline-none cursor-pointer min-w-[120px]" 
                                                    data-original-status-id="<?php echo $order->getStatusId(); ?>">
                                                <?php foreach ($statuses as $status): ?>
                                                    <option value="<?php echo $status['id']; ?>" <?php echo $status['id'] == $order->getStatusId() ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($status['status_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="status-save-btn w-8 h-8 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                    data-order-id="<?php echo $order->getId(); ?>" disabled>
                                                <i class="fa-solid fa-check text-xs"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-xs text-slate-400 font-medium">Read-only</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-white">
            <p class="text-sm text-slate-400">
                Showing <span class="font-medium text-slate-600"><?php echo count($orders); ?></span> orders
            </p>
            <nav class="inline-flex -space-x-px rounded-md space-x-2">
                <button class="inline-flex items-center px-2 py-1.5 text-slate-400 border border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">1</button>
                <button class="inline-flex items-center px-2 py-1.5 text-slate-400 border border-slate-200 rounded-md hover:bg-slate-50 transition-colors">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </nav>
        </div>
    </div>
</main>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-slate-900">Order Details</h2>
            <button onclick="closeOrderDetails()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>
        <div id="orderDetailsContent">
            <div class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-500"></i>
                <p class="text-sm text-slate-400 mt-2">Loading order details...</p>
            </div>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }
.modal {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    animation: modalSlideIn 0.3s ease;
}
@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.detail-row { display: flex; padding: 8px 0; border-bottom: 1px solid #F1F5F9; }
.detail-label { width: 140px; font-weight: 600; color: #475569; font-size: 13px; flex-shrink: 0; }
.detail-value { flex: 1; color: #1E293B; font-size: 13px; }
.order-item-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #F1F5F9; }
.order-item-row:last-child { border-bottom: none; }
</style>

<script>
// Search
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const orderId = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        const customer = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
        row.style.display = (orderId.includes(searchTerm) || customer.includes(searchTerm)) ? '' : 'none';
    });
});

// Status Filter
document.getElementById('statusFilter').addEventListener('change', function() {
    const statusId = this.value;
    document.querySelectorAll('tbody tr').forEach(row => {
        const statusCell = row.querySelector('td:nth-child(6) span');
        if (!statusCell) return;
        if (statusId === '') {
            row.style.display = '';
        } else {
            const statusText = statusCell.textContent?.toLowerCase().trim() || '';
            const statusMap = { '1': 'pending', '2': 'accepted', '3': 'preparing', '4': 'ready', '5': 'completed', '6': 'cancelled' };
            const targetStatus = statusMap[statusId] || statusId;
            row.style.display = statusText.includes(targetStatus) ? '' : 'none';
        }
    });
});

// Status Update
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const action = this.closest('.status-action');
        const button = action?.querySelector('.status-save-btn');
        if (button) button.disabled = this.value === this.dataset.originalStatusId;
    });
});

document.querySelectorAll('.status-save-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const action = this.closest('.status-action');
        const select = action?.querySelector('.status-select');
        if (!select) return;
        const orderId = this.dataset.orderId;
        const statusId = select.value;
        const statusName = select.options[select.selectedIndex]?.textContent?.trim() || 'selected status';
        
        if (confirm(`Update order #${orderId} status to "${statusName}"?`)) {
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';
            fetch(window.location.pathname, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&status_id=${statusId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    select.dataset.originalStatusId = statusId;
                    alert('Order status updated successfully!');
                    location.reload();
                } else {
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                this.disabled = false;
                this.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                alert('Error updating order status: ' + error.message);
            });
        }
    });
});

// View Details
document.querySelectorAll('.btn-view-details').forEach(btn => {
    btn.addEventListener('click', function() { openOrderDetails(this.dataset.orderId); });
});

function openOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const content = document.getElementById('orderDetailsContent');
    content.innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-500"></i><p class="text-sm text-slate-400 mt-2">Loading order details...</p></div>';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_order_details&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.order) {
            renderOrderDetails(data.order);
        } else {
            content.innerHTML = `<div class="text-center py-8 text-red-500"><i class="fa-solid fa-circle-exclamation text-3xl"></i><p class="text-sm mt-2">${data.message || 'Failed to load order details.'}</p></div>`;
        }
    })
    .catch(error => {
        content.innerHTML = `<div class="text-center py-8 text-red-500"><i class="fa-solid fa-circle-exclamation text-3xl"></i><p class="text-sm mt-2">Error loading order details: ${error.message}</p></div>`;
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
    const statusColor = statusColors[order.status_name?.toLowerCase()] || 'bg-gray-100 text-gray-800';
    
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <div class="order-item-row">
                    <span>${item.food_name} (Qty: ${item.quantity})</span>
                    <span class="font-medium">$${parseFloat(item.subtotal).toFixed(2)}</span>
                </div>
            `;
        });
    } else {
        itemsHtml = '<p class="text-sm text-slate-400">No items found</p>';
    }
    
    // ✅ Check if payment method is Cash on Delivery or digital
    const paymentMethod = order.payment_method || 'Cash on Delivery';
    const accountName = order.account_name || '';
    const accountNumber = order.account_number || '';
    const transactionNo = order.transaction_no || '';
    const paymentStatus = order.payment_status || '';
    const isCOD = paymentMethod === 'Cash on Delivery';
    
    // ✅ Build payment section based on payment type
    let paymentHtml = '';
    if (isCOD) {
        paymentHtml = `
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fa-solid fa-truck mr-1.5"></i> Cash on Delivery
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status</span>
                <span class="detail-value">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Pending (Pay upon delivery)
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Account Details</span>
                <span class="detail-value text-slate-400 text-sm italic">Not applicable for Cash on Delivery</span>
            </div>
        `;
    } else {
        paymentHtml = `
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fa-solid fa-credit-card mr-1.5"></i> ${paymentMethod}
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Status</span>
                <span class="detail-value">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${paymentStatus === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${paymentStatus ? paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1) : 'Pending'}
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Account Name</span>
                <span class="detail-value font-medium">${accountName || 'N/A'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Account Number</span>
                <span class="detail-value font-mono text-sm">${accountNumber || 'N/A'}</span>
            </div>
            ${transactionNo ? `<div class="detail-row"><span class="detail-label">Transaction No.</span><span class="detail-value font-mono text-sm">${transactionNo}</span></div>` : ''}
        `;
    }
    
    // ✅ Transaction Image Section
    let imageHtml = '';
    const imagePath = order.transaction_image || '';
    
    if (imagePath && imagePath !== 'N/A' && imagePath !== '') {
        const imageUrl = '/Campus-Food-Ordering-System/Public/' + imagePath;
        
        imageHtml = `
            <div class="border-t border-slate-100 pt-3">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Transaction Image</h3>
                <div class="bg-slate-50 rounded-lg p-3 text-center">
                    <div class="flex flex-col items-center">
                        <img src="${imageUrl}" 
                             class="max-w-[300px] max-h-[300px] rounded-lg border border-slate-200 shadow-sm object-cover" 
                             alt="Transaction Image"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'text-center py-4\\'><i class=\\'fa-regular fa-image text-3xl text-red-300 mb-2 block\\'></i><span class=\\'text-sm text-red-500\\'>Image not found</span></div>'">
                        <div class="mt-2 flex gap-3">
                            <a href="${imageUrl}" target="_blank" 
                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                <i class="fa-regular fa-eye mr-1"></i> View Full Size
                            </a>
                            <a href="${imageUrl}" download 
                               class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                                <i class="fa-regular fa-download mr-1"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else if (isCOD) {
        imageHtml = `
            <div class="border-t border-slate-100 pt-3">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Transaction Image</h3>
                <div class="bg-slate-50 rounded-lg p-3 text-center">
                    <span class="text-sm text-slate-400 italic">Cash on Delivery - No image required</span>
                </div>
            </div>
        `;
    } else {
        imageHtml = `
            <div class="border-t border-slate-100 pt-3">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Transaction Image</h3>
                <div class="bg-slate-50 rounded-lg p-3 text-center">
                    <span class="text-sm text-slate-400 italic">No transaction image uploaded</span>
                </div>
            </div>
        `;
    }
    
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="space-y-4">
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-medium ${statusColor}">
                        ${order.status_name || 'N/A'}
                    </span>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value font-medium">#${order.id}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer Name</span>
                <span class="detail-value">${order.customer_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value">${order.customer_phone}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Delivery Address</span>
                <span class="detail-value">${order.delivery_address}</span>
            </div>
            
            ${paymentHtml}
            
            <div class="border-t border-slate-100 pt-3">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Order Items</h3>
                <div class="bg-slate-50 rounded-lg p-3">
                    ${itemsHtml}
                </div>
            </div>
            
            ${imageHtml}
            
            <div class="border-t border-slate-100 pt-3 flex justify-between font-bold text-slate-900">
                <span>Total Amount</span>
                <span class="text-emerald-600">$${parseFloat(order.total_amount).toFixed(2)}</span>
            </div>
            
            <div class="text-xs text-slate-400 text-right border-t border-slate-100 pt-3">
                Order placed: ${new Date(order.order_date).toLocaleString()}
            </div>
        </div>
    `;
}

function closeOrderDetails() {
    document.getElementById('orderDetailsModal').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrderDetails();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>