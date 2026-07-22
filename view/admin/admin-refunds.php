<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin role
if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../inc/refund_helpers.php';
require_once __DIR__ . '/../../inc/admin_helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/settings_helper.php';

if (!hasPermission('manage_orders')) {
    renderAdminPermissionDeniedPage('Access denied', 'refunds');
}

$refundController = getRefundController();

$successMessage = '';
$errorMessage = '';

// Handle POST actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'approve') {
        $result = $refundController->approveRefund();
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    } elseif ($action === 'reject') {
        $result = $refundController->rejectRefund();
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Fetch refunds data
$refundsData = $refundController->getRefundRequests();
$refunds = $refundsData['success'] ? $refundsData['data'] : [];

$pageTitle = 'Foodie - Manage Refunds';
$activePage = 'refunds';

$userController = getUserController();
$currentUser = $userController->getCurrentUser();

include __DIR__ . '/includes/sidebar.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manage Refund Requests</h1>
        <p class="text-gray-400 text-sm mt-1">Review, approve or reject customer refund requests</p>
    </div>
</div>

<!-- Notifications -->
<?php if (!empty($successMessage)): ?>
    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm rounded-r-lg">
        <i class="fa-solid fa-circle-check mr-2"></i> <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r-lg">
        <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="bg-white border border-gray-100 rounded-xl shadow-sm flex flex-col overflow-hidden mb-6">
    <div class="p-5 flex items-center justify-between border-b border-gray-50">
        <div class="relative w-full max-w-xl">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Search refunds by Customer name or Order ID..." class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-gray-400">
        </div>
        <div class="flex items-center space-x-3">
            <select id="statusFilter" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 bg-white">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </div>

    <!-- Refunds Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse" id="refundsTable">
            <thead>
                <tr class="bg-gray-50 text-gray-400 font-semibold text-xs uppercase border-b border-gray-100">
                    <th class="py-4 px-6 text-center">Refund ID</th>
                    <th class="py-4 px-6">Order ID</th>
                    <th class="py-4 px-6">Customer</th>
                    <th class="py-4 px-6">Amount</th>
                    <th class="py-4 px-6">Reason</th>
                    <th class="py-4 px-6">Status</th>
                    <th class="py-4 px-6">Requested Date</th>
                    <th class="py-4 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 text-sm text-gray-600">
                <?php if (empty($refunds)): ?>
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-400">No refund requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($refunds as $refund): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors refund-row" 
                            data-customer="<?php echo htmlspecialchars(strtolower($refund['requested_by_name'] ?? '')); ?>"
                            data-order-id="<?php echo $refund['order_id']; ?>"
                            data-status="<?php echo strtolower($refund['refund_status_name'] ?? 'pending'); ?>">
                            <td class="py-4 px-6 text-center font-medium text-gray-900">#<?php echo $refund['id']; ?></td>
                            <td class="py-4 px-6 font-semibold text-indigo-600">#<?php echo $refund['order_id']; ?></td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($refund['requested_by_name'] ?? 'Guest'); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($refund['requested_by_email'] ?? ''); ?></div>
                            </td>
                            <td class="py-4 px-6 font-semibold text-gray-900"><?php echo app_format_price((float) ($refund['order_total'] ?? 0)); ?></td>
                            <td class="py-4 px-6 max-w-xs truncate" title="<?php echo htmlspecialchars($refund['reason']); ?>">
                                <?php echo htmlspecialchars($refund['reason']); ?>
                            </td>
                            <td class="py-4 px-6">
                                <?php echo getRefundStatusBadge((int) $refund['refund_status_id']); ?>
                            </td>
                            <td class="py-4 px-6 text-xs text-gray-400">
                                <?php echo date('M d, Y h:i A', strtotime($refund['created_at'])); ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="/Campus-Food-Ordering-System/Public/order/receipt?id=<?php echo $refund['order_id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Print Receipt">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    <?php if ((int)$refund['refund_status_id'] === 1): ?>
                                        <button onclick="openProcessModal(<?php echo $refund['id']; ?>, <?php echo $refund['order_id']; ?>, '<?php echo htmlspecialchars($refund['requested_by_name'] ?? 'Guest'); ?>', '<?php echo app_format_price((float) ($refund['order_total'] ?? 0)); ?>')" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium" title="Process Refund">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Process Refund Modal -->
<div id="processModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-all duration-300">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Process Refund Request</h2>
            <button onclick="closeProcessModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="mb-4 text-sm text-gray-500">
            <p><strong>Refund ID:</strong> #<span id="modalRefundId"></span></p>
            <p><strong>Order ID:</strong> #<span id="modalOrderId"></span></p>
            <p><strong>Customer:</strong> <span id="modalCustomer"></span></p>
            <p><strong>Total Amount:</strong> <span id="modalAmount"></span></p>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="refund_id" id="postRefundId">
            <input type="hidden" name="action" id="postAction" value="approve">
            
            <div>
                <label for="refundNotes" class="block text-sm font-medium text-slate-700 mb-1">Response Notes (Optional)</label>
                <textarea id="refundNotes" name="notes" rows="3" 
                          class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm placeholder-slate-400"
                          placeholder="Provide any context or notes for the customer..."></textarea>
            </div>
            
            <div class="flex space-x-3 pt-2">
                <button type="submit" onclick="setAction('reject')"
                        class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Reject (Cancel)
                </button>
                <button type="submit" onclick="setAction('approve')"
                        class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Approve & Refund
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProcessModal(refundId, orderId, customerName, totalAmount) {
    document.getElementById('modalRefundId').textContent = refundId;
    document.getElementById('modalOrderId').textContent = orderId;
    document.getElementById('modalCustomer').textContent = customerName;
    document.getElementById('modalAmount').textContent = totalAmount;
    
    document.getElementById('postRefundId').value = refundId;
    document.getElementById('refundNotes').value = '';
    
    const modal = document.getElementById('processModal');
    modal.classList.remove('hidden');
}

function closeProcessModal() {
    const modal = document.getElementById('processModal');
    modal.classList.add('hidden');
}

function setAction(action) {
    document.getElementById('postAction').value = action;
}

// Search and Filter Script
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.refund-row');

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value.toLowerCase();

        rows.forEach(row => {
            const customer = row.getAttribute('data-customer');
            const orderId = row.getAttribute('data-order-id');
            const status = row.getAttribute('data-status');

            const matchesSearch = customer.includes(query) || orderId.includes(query);
            const matchesStatus = !selectedStatus || status === selectedStatus;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
