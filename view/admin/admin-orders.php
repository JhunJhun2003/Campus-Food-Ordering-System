<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../entrance/includes/permissions.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/order_helpers.php';

// ============================================
// 1. AUTHENTICATION & AUTHORIZATION
// ============================================

requireLogin();
requirePermission('manage_orders');

// ============================================
// 2. BUSINESS LOGIC
// ============================================

// ✅ Use helpers - NO 'new' keyword!
$adminController = getAdminController();
$currentUser = $adminController->getCurrentUser();

$orderController = getOrderController();

// Handle AJAX request for order details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // Update status
    if ($action === 'update_status') {
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $statusId = isset($_POST['status_id']) ? (int) $_POST['status_id'] : 0;

        if ($orderId <= 0 || $statusId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid order or status.'
            ]);
            exit();
        }

        echo json_encode($orderController->updateStatus($orderId, $statusId));
        exit();
    }
    
    // Get order details for modal
    if ($action === 'get_order_details') {
        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
            exit();
        }
        
        try {
            // Get order from repository
            $order = $orderController->getOrder($orderId);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found.']);
                exit();
            }
            
            // Get order items
            $items = $orderController->getOrderItems($orderId);
            
            $orderData = [
                'id' => $order->getId(),
                'order_date' => $order->getOrderDate()->format('Y-m-d H:i:s'),
                'customer_name' => $order->getCustomerName() ?? 'Unknown',
                'customer_phone' => $order->getCustomerPhone() ?? 'N/A',
                'delivery_address' => $order->getDeliveryAddress() ?? 'N/A',
                'payment_method' => $order->getPaymentMethod() ?? 'N/A',
                'account_name' => $order->getAccountName() ?? 'N/A',
                'account_number' => $order->getAccountNumber() ?? 'N/A',
                'transaction_image' => $order->getTransactionImage() ?? null,
                'total_amount' => $order->getTotalAmount(),
                'items' => $items,
                'status_id' => $order->getStatusId()
            ];
            
            // Get status name
            $statuses = $orderController->getStatuses();
            foreach ($statuses as $status) {
                if ($status['id'] == $order->getStatusId()) {
                    $orderData['status_name'] = $status['status_name'];
                    break;
                }
            }
            
            echo json_encode(['success' => true, 'order' => $orderData]);
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

$orders = $orderController->index();
$statuses = $orderController->getStatuses();

// ... rest of the HTML remains the same ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
 <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-link.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }
        .sidebar-link:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
        .status-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            background: #F9FAFB;
        }
        .status-select {
            min-width: 132px;
            border: 0;
            background: transparent;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
        }
        .status-save-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #4F46E5;
            color: white;
            transition: all 0.2s ease;
        }
        .status-save-btn:hover:not(:disabled) {
            background: #4338CA;
        }
        .status-save-btn:disabled {
            background: #E5E7EB;
            color: #9CA3AF;
            cursor: not-allowed;
        }
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
        .modal-overlay.active {
            display: flex;
        }
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
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0F172A;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #94A3B8;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: #F1F5F9;
            color: #0F172A;
        }
        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .detail-label {
            width: 140px;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            flex-shrink: 0;
        }
        .detail-value {
            flex: 1;
            color: #1E293B;
            font-size: 13px;
        }
        .detail-value .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .order-item-row:last-child {
            border-bottom: none;
        }
        .modal .btn-view-details {
            color: #4F46E5;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal .btn-view-details:hover {
            color: #4338CA;
        }
        .transaction-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
        }
        .transaction-image-placeholder {
            color: #94A3B8;
            font-size: 13px;
            font-style: italic;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen text-gray-800 antialiased">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-gray-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-black mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-20 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-black"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-black">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-gray-500 rounded-lg font-medium transition-colors">
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
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-gray-500 hover:bg-red-50 hover:text-red-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
                <p class="text-gray-400 text-sm mt-1">Manage all customer orders</p>
            </div>
            <div class="flex items-center space-x-3">
                <select class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white" id="statusFilter">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['id']; ?>">
                            <?php echo ucfirst($status['status_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
        
        <div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden">
            
            <!-- Search & Filter Bar -->
            <div class="p-5 flex items-center justify-between border-b border-gray-50">
                <div class="relative w-full max-w-xl">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </span>
                    <input type="text" placeholder="Search orders by ID or customer..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400" id="searchInput">
                </div>
                <button class="flex items-center justify-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors ml-4">
                    <i class="fa-solid fa-filter text-gray-700 text-sm"></i>
                </button>
            </div>

            <!-- Table -->
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
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
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
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6 text-center text-gray-400 text-xs"><?php echo $counter++; ?></td>
                                    <td class="py-4 px-6 font-medium text-gray-900">#<?php echo $order->getId(); ?></td>
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
                                            <!-- View Details Button -->
                                            <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium btn-view-details" 
                                                    data-order-id="<?php echo $order->getId(); ?>"
                                                    title="View Order Details">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            <!-- Status Update -->
                                            <div class="status-action">
                                                <select class="status-select" data-original-status-id="<?php echo $order->getStatusId(); ?>" aria-label="Order #<?php echo $order->getId(); ?> status">
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?php echo $status['id']; ?>" <?php echo $status['id'] == $order->getStatusId() ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($status['status_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button"
                                                        class="status-save-btn"
                                                        data-order-id="<?php echo $order->getId(); ?>"
                                                        title="Save status"
                                                        aria-label="Save order #<?php echo $order->getId(); ?> status"
                                                        disabled>
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

            <!-- Pagination -->
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
    </main>

    <!-- ===== ORDER DETAILS MODAL ===== -->
    <div id="orderDetailsModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button onclick="closeOrderDetails()" class="modal-close">
                    <i class="fa-solid fa-xmark"></i>
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

    <script>
        // ============================================
        // SEARCH
        // ============================================
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const orderId = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
                const customer = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
                
                if (orderId.includes(searchTerm) || customer.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // ============================================
        // STATUS FILTER
        // ============================================
        document.getElementById('statusFilter')?.addEventListener('change', function() {
            const statusId = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(6) span');
                if (!statusCell) return;
                
                if (statusId === '' || statusCell.textContent?.toLowerCase().includes(statusId)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
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
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
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
            
            // Show loading
            content.innerHTML = `
                <div class="text-center py-8">
                    <i class="fa-solid fa-spinner fa-spin text-3xl text-indigo-500"></i>
                    <p class="text-sm text-gray-400 mt-2">Loading order details...</p>
                </div>
            `;
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Fetch order details
            fetch('admin-orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
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
                        <p class="text-sm mt-2">Error loading order details: ${error.message}</p>
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
            
            const statusColor = statusColors[order.status_name?.toLowerCase()] || 'bg-gray-100 text-gray-800';
            
            // Format items HTML
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
                itemsHtml = `<p class="text-sm text-gray-400">No items found</p>`;
            }
            
            // Transaction image
            let imageHtml = '';
            if (order.transaction_image) {
                imageHtml = `
                    <img src="/Campus-Food-Ordering-System/Public/${order.transaction_image}" 
                         class="transaction-image" 
                         alt="Transaction Image"
                         onerror="this.style.display='none'">
                `;
            } else {
                imageHtml = `<span class="transaction-image-placeholder">No transaction image uploaded</span>`;
            }
            
            const content = document.getElementById('orderDetailsContent');
            content.innerHTML = `
                <div class="space-y-4">
                    <!-- Status -->
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-badge ${statusColor}">${order.status_name || 'N/A'}</span>
                        </span>
                    </div>
                    
                    <!-- Customer Info -->
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
                    
                    <!-- Payment Info -->
                    <div class="detail-row">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">${order.payment_method}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Name</span>
                        <span class="detail-value">${order.account_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Number</span>
                        <span class="detail-value">${order.account_number}</span>
                    </div>
                    
                    <!-- Transaction Image -->
                    <div class="detail-row">
                        <span class="detail-label">Transaction Image</span>
                        <span class="detail-value">${imageHtml}</span>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="border-t border-gray-100 pt-3">
                        <h3 class="font-semibold text-gray-900 text-sm mb-2">Order Items</h3>
                        <div class="bg-gray-50 rounded-lg p-3">
                            ${itemsHtml}
                        </div>
                    </div>
                    
                    <!-- Total -->
                    <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900">
                        <span>Total Amount</span>
                        <span class="text-emerald-600">$${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                    
                    <!-- Date -->
                    <div class="text-xs text-gray-400 text-right border-t border-gray-100 pt-3">
                        Order placed: ${new Date(order.order_date).toLocaleString()}
                    </div>
                </div>
            `;
        }

        function closeOrderDetails() {
            const modal = document.getElementById('orderDetailsModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal on overlay click
        document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetails();
            }
        });
    </script>

</body>
</html>