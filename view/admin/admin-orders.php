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
require_once __DIR__ . '/../../inc/order_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';

requireLogin();
if (!hasPermission('manage_orders')) {
    renderAdminPermissionDeniedPage('Access denied', 'orders');
}

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$orderController = getOrderController();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    // Update status
    if ($action === 'update_status') {
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $statusId = isset($_POST['status_id']) ? (int) $_POST['status_id'] : 0;

        if ($orderId <= 0 || $statusId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order or status.']);
            exit();
        }

        echo json_encode($orderController->updateStatus($orderId, $statusId));
        exit();
    }
    
    // Get order details with payment info
    if ($action === 'get_order_details') {
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
            exit();
        }
        
        try {
            // Use the order repository with payment details
            $orderRepository = new \App\Order\Infrastructure\Repositories\OrderRepository();
            $orderData = $orderRepository->findByIdWithDetails($orderId);
            
            if (!$orderData) {
                echo json_encode(['success' => false, 'message' => 'Order not found.']);
                exit();
            }
            
            $items = $orderRepository->getOrderItems($orderId);
            
            // Get payment details
            $paymentMethod = $orderData['payment_method_name'] ?? 'Cash on Delivery';
            $accountName = $orderData['payment_account_name'] ?? '';
            $accountNumber = $orderData['payment_account_number'] ?? '';
            $transactionNo = $orderData['transaction_no'] ?? '';
            $paymentStatus = $orderData['payment_status_name'] ?? '';
            $isCOD = $paymentMethod === 'Cash on Delivery';
            
            echo json_encode([
                'success' => true,
                'order' => [
                    'id' => $orderData['id'],
                    'order_date' => $orderData['order_date'],
                    'customer_name' => $orderData['customer_name'] ?? $orderData['customer_name_from_user'] ?? 'Unknown',
                    'customer_phone' => $orderData['customer_phone'] ?? $orderData['customer_phone_from_user'] ?? 'N/A',
                    'delivery_address' => $orderData['delivery_address'] ?? 'N/A',
                    'payment_method' => $paymentMethod,
                    'account_name' => $accountName,
                    'account_number' => $accountNumber,
                    'transaction_no' => $transactionNo,
                    'payment_status' => $paymentStatus,
                    'is_cod' => $isCOD,
                    'total_amount' => $orderData['total_amount'],
                    'items' => $items,
                    'status_id' => $orderData['status_id'],
                    'status_name' => $orderData['status_name'] ?? 'pending',
                    'transaction_image' => $orderData['transaction_image'] ?? null
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

$pageTitle = 'Foodie - Orders';
$activePage = 'orders';

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
        <p class="text-gray-400 text-sm mt-1">Manage all customer orders</p>
    </div>
    <div class="flex items-center space-x-3">
        <select id="statusFilter" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
            <option value="">All Status</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?php echo $status['id']; ?>">
                    <?php echo ucfirst($status['status_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button onclick="location.reload()" class="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
            <i class="fa-solid fa-rotate"></i>
            <span>Refresh</span>
        </button>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
    <div class="p-5 flex items-center justify-between border-b border-gray-50">
        <div class="relative w-full max-w-xl">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search orders by ID or customer..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
        </div>
        <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors ml-4">
            <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs font-semibold uppercase tracking-wider">
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
            <tbody id="ordersTableBody" class="divide-y divide-gray-100 text-sm text-gray-700">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-400">
                            <i class="fa-regular fa-receipt text-4xl block mb-3"></i>
                            <p class="text-sm font-medium">No orders found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $counter = 1; ?>
                    <?php foreach ($orders as $order): ?>
                        <?php 
                            $orderId = $order->getId();
                            $statusId = $order->getStatusId();
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
                        <tr class="hover:bg-gray-50/50 transition-colors" data-order-id="<?php echo $orderId; ?>" data-status-id="<?php echo $statusId; ?>">
                            <td class="py-4 px-6 text-center text-gray-400 text-xs"><?php echo $counter++; ?></td>
                            <td class="py-4 px-6 font-medium text-gray-900">#<?php echo $orderId; ?></td>
                            <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($order->getCustomerName() ?? 'Unknown'); ?></td>
                            <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($order->getCustomerPhone() ?? 'N/A'); ?></td>
                            <td class="py-4 px-6 font-medium text-gray-900">$<?php echo number_format($order->getTotalAmount(), 2); ?></td>
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
                                    $colorClass = $statusColors[$statusName] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $colorClass; ?>">
                                    <?php echo ucfirst($statusName); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-gray-400 text-xs">
                                <?php echo $order->getOrderDate()->format('M d, Y h:i A'); ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center space-x-2">
                                    <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium btn-view-details" data-order-id="<?php echo $orderId; ?>" title="View Order Details">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <a href="/Campus-Food-Ordering-System/Public/receipt.php?id=<?php echo $orderId; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Print Receipt">
    <i class="fa-solid fa-print"></i>
</a>
                                    <!-- <?php if ($canRefund && !$hasPendingRefund): ?>
                                        <button class="text-emerald-600 hover:text-emerald-800 text-sm font-medium btn-refund-order" data-order-id="<?php echo $orderId; ?>" title="Request Refund">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    <?php endif; ?> -->
                                    <div class="status-action">
                                        <select class="status-select" data-original-status-id="<?php echo $order->getStatusId(); ?>">
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?php echo $status['id']; ?>" <?php echo $status['id'] == $order->getStatusId() ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($status['status_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="status-save-btn" data-order-id="<?php echo $orderId; ?>" title="Save status" disabled>
                                            <i class="fa-solid fa-check text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-100 flex items-center justify-between bg-white">
        <p class="text-sm text-gray-400">
            Showing <span class="font-medium text-gray-600"><?php echo count($orders); ?></span> orders
        </p>
        <nav class="inline-flex -space-x-px rounded-md space-x-2" aria-label="Pagination">
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button class="inline-flex items-center px-3.5 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md">
                1
            </button>
            <button class="inline-flex items-center px-2 py-1.5 text-gray-400 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
        </nav>
    </div>
</div>

<!-- ============================================ -->
<!-- ORDER DETAILS MODAL -->
<!-- ============================================ -->
<div id="orderDetailsModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Order Details</h2>
            <button onclick="closeOrderDetails()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div id="orderDetailsContent">
            <div class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-500"></i>
                <p class="text-sm text-gray-400 mt-2">Loading order details...</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- REFUND MODAL -->
<!-- ============================================ -->
<div id="refundModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
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
                          class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-slate-400"
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

<!-- ============================================ -->
<!-- TOAST -->
<!-- ============================================ -->
<div id="toast" class="toast fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-lg transform translate-y-24 opacity-0 transition-all duration-300 z-50 max-w-md"></div>

<script>
// ============================================
// SEARCH
// ============================================
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#ordersTableBody tr').forEach(row => {
        const orderId = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
        const customer = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
        row.style.display = (orderId.includes(searchTerm) || customer.includes(searchTerm)) ? '' : 'none';
    });
});

// ============================================
// STATUS FILTER
// ============================================
document.getElementById('statusFilter').addEventListener('change', function() {
    const statusId = this.value;
    document.querySelectorAll('#ordersTableBody tr').forEach(row => {
        const statusCell = row.querySelector('td:nth-child(6) span');
        if (!statusCell) return;
        if (statusId === '') {
            row.style.display = '';
        } else {
            const statusText = statusCell.textContent?.toLowerCase() || '';
            const selectedStatus = document.querySelector('#statusFilter option[value="' + statusId + '"]')?.textContent?.toLowerCase() || '';
            row.style.display = statusText.includes(selectedStatus) ? '' : 'none';
        }
    });
});

// ============================================
// STATUS UPDATE
// ============================================
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const action = this.closest('.status-action');
        const button = action?.querySelector('.status-save-btn');
        if (!button) return;
        button.disabled = this.value === this.dataset.originalStatusId;
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

            fetch('admin-orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&status_id=${statusId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    select.dataset.originalStatusId = statusId;
                    showToast('Order status updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                this.disabled = false;
                this.innerHTML = '<i class="fa-solid fa-check text-xs"></i>';
                showToast('Error updating order status', 'error');
                console.error(error);
            });
        }
    });
});

// ============================================
// VIEW ORDER DETAILS
// ============================================
document.querySelectorAll('.btn-view-details').forEach(btn => {
    btn.addEventListener('click', function() {
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
            <p class="text-sm text-gray-400 mt-2">Loading order details...</p>
        </div>
    `;
    
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
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
    .catch(error => {
        content.innerHTML = `
            <div class="text-center py-8 text-red-500">
                <i class="fa-solid fa-circle-exclamation text-3xl"></i>
                <p class="text-sm mt-2">Error loading order details</p>
            </div>
        `;
        console.error(error);
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
        itemsHtml = `<p class="text-sm text-gray-400">No items found</p>`;
    }
    
    // Payment details section
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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Pending (Pay upon delivery)
                </span>
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
                    <i class="fa-solid fa-credit-card mr-1.5"></i> ${order.payment_method}
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
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="font-medium text-slate-500">Account Number</span>
                <span class="font-mono text-sm">${order.account_number || 'N/A'}</span>
            </div>
            ${order.transaction_no ? `
            <div class="flex justify-between py-2">
                <span class="font-medium text-slate-500">Transaction No.</span>
                <span class="font-mono text-sm">${order.transaction_no}</span>
            </div>` : ''}
        `;
    }
    
    let imageHtml = '';
    const imagePath = order.transaction_image || '';
    
    if (imagePath && imagePath !== 'N/A' && imagePath !== '') {
        const imageUrl = '/Campus-Food-Ordering-System/Public/' + imagePath;
        
        imageHtml = `
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
        `;
    } else if (isCOD) {
        imageHtml = `
            <div class="text-center py-4">
                <i class="fa-solid fa-truck text-3xl text-green-400 mb-2 block"></i>
                <span class="text-sm text-slate-500">Cash on Delivery - No transaction image required</span>
            </div>
        `;
    } else {
        imageHtml = `
            <div class="text-center py-4">
                <i class="fa-regular fa-image text-3xl text-slate-300 mb-2 block"></i>
                <span class="text-sm text-slate-400">No transaction image uploaded</span>
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
                        <span>${order.customer_name}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Phone</span>
                        <span>${order.customer_phone}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-slate-100">
                        <span class="font-medium text-slate-500">Address</span>
                        <span>${order.delivery_address}</span>
                    </div>
                </div>
                <div>
                    ${paymentHtml}
                </div>
            </div>
            
            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Order Items</h3>
                <div class="bg-slate-50 rounded-lg p-3">
                    ${itemsHtml}
                </div>
            </div>
            
            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-semibold text-slate-900 text-sm mb-2">Transaction Image</h3>
                <div class="bg-slate-50 rounded-lg p-3 text-center">
                    ${imageHtml}
                </div>
            </div>
            
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
    const modal = document.getElementById('orderDetailsModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderDetails();
    }
});

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
    document.getElementById('refundOrderId').value = orderId;
    document.getElementById('refundReason').value = '';
    document.getElementById('refundResponse').classList.add('hidden');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Close refund modal
function closeRefundModal() {
    const modal = document.getElementById('refundModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
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

// ============================================
// TOAST
// ============================================
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg transform transition-all duration-300 z-50 max-w-md';
    const colors = {
        success: { bg: '#10B981', text: 'white' },
        error: { bg: '#EF4444', text: 'white' },
        info: { bg: '#3B82F6', text: 'white' }
    };
    const style = colors[type] || colors.success;
    toast.style.background = style.bg;
    toast.style.color = style.text;
    setTimeout(() => {
        toast.classList.remove('translate-y-24', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);
    setTimeout(() => {
        toast.classList.add('translate-y-24', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
    }, 3000);
}
</script>

</main>
</body>
</html>