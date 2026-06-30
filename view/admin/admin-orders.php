<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;
use App\Order\Presentation\Http\Controllers\OrderController;

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

$orderController = new OrderController();
$orders = $orderController->index();
$statuses = $orderController->getStatuses();
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
        .status-dropdown {
            transition: all 0.2s ease;
        }
        .status-dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
            min-width: 120px;
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
                <select class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['id']; ?>">
                            <?php echo ucfirst($status['status_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="flex items-center space-x-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
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
                            <th class="py-3 px-6 text-center">Action</th>
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
                                        <div class="relative inline-block text-left status-dropdown">
                                            <button class="inline-flex items-center space-x-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 bg-indigo-100 rounded-md hover:bg-indigo-200 transition-colors focus:outline-none">
                                                <span>Update</span>
                                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                            </button>
                                            
                                            <div class="dropdown-menu absolute right-0 mt-1 w-32 bg-white rounded-md shadow-lg border border-gray-100 z-10 p-1 space-y-1">
                                                <?php foreach ($statuses as $status): ?>
                                                    <button class="w-full text-left block px-2 py-1 text-xs font-semibold rounded hover:bg-gray-50 transition-colors status-btn" 
                                                            data-order-id="<?php echo $order->getId(); ?>"
                                                            data-status-id="<?php echo $status['id']; ?>"
                                                            data-status-name="<?php echo $status['status_name']; ?>">
                                                        <?php echo ucfirst($status['status_name']); ?>
                                                    </button>
                                                <?php endforeach; ?>
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

    <script>
        // Search functionality
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

        // Status filter
        document.querySelector('select')?.addEventListener('change', function() {
            const status = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(6) span');
                if (!statusCell) return;
                
                const rowStatus = statusCell.textContent?.toLowerCase() || '';
                
                if (status === '' || rowStatus.includes(status)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Update status via AJAX
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const statusId = this.dataset.statusId;
                const statusName = this.dataset.statusName;
                
                if (confirm(`Update order #${orderId} status to "${statusName}"?`)) {
                    // AJAX call to update status
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
                            alert('Order status updated successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error updating order status');
                    });
                }
            });
        });

        // Refresh button
        document.querySelector('.bg-indigo-600')?.addEventListener('click', function() {
            location.reload();
        });
    </script>

</body>
</html>