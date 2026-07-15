<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 1. AUTHENTICATION
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/order_helpers.php';
require_once __DIR__ . '/../inc/admin_helpers.php';

// ============================================
// 2. BUSINESS LOGIC
// ============================================

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($orderId <= 0) {
    die('Invalid order ID.');
}

$orderController = getOrderController();

// ✅ Use the correct method - getOrderById or index
// Try to get order using the repository directly
try {
    $orderRepository = new \App\Order\Infrastructure\Repositories\OrderRepository();
    $order = $orderRepository->findById($orderId);
} catch (\Exception $e) {
    $order = null;
}

if (!$order) {
    die('Order not found.');
}

// Check if user owns this order or is admin/staff
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? 'customer';

if ($userRole !== 'admin' && $userRole !== 'staff' && $order->getUserId() !== $userId) {
    die('You do not have permission to view this receipt.');
}

// Get order items
$orderItems = $orderRepository->getOrderItems($orderId);

// Get payment details
$orderData = $orderRepository->findByIdWithDetails($orderId);

// Get statuses
$statuses = $orderController->getStatuses();
$statusName = '';
foreach ($statuses as $status) {
    if ($status['id'] == $order->getStatusId()) {
        $statusName = $status['status_name'];
        break;
    }
}

// Get payment method
$paymentMethod = $orderData['payment_method_name'] ?? 'Cash on Delivery';
$isCOD = $paymentMethod === 'Cash on Delivery';

// Get transaction image
$transactionImage = $orderData['transaction_image'] ?? null;

// ============================================
// 3. VIEW RENDER - PRINT STYLED
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt #<?php echo $orderId; ?></title>
    <style>
        /* Print Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
        }
        
        .receipt {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .receipt-header .logo {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        
        .receipt-header .logo span {
            color: #4f46e5;
        }
        
        .receipt-header .subtitle {
            font-size: 12px;
            color: #666;
        }
        
        .receipt-header .order-id {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
            color: #555;
        }
        
        .divider {
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 13px;
        }
        
        .row.label {
            font-weight: bold;
            color: #555;
        }
        
        .row.total {
            font-size: 16px;
            font-weight: bold;
            padding-top: 10px;
            border-top: 2px solid #333;
            margin-top: 10px;
        }
        
        .row.total .amount {
            color: #2d7d46;
            font-size: 18px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .items-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .items-table td {
            padding: 4px 0;
            font-size: 13px;
        }
        
        .items-table .item-name {
            width: 60%;
        }
        
        .items-table .item-qty {
            width: 15%;
            text-align: center;
        }
        
        .items-table .item-price {
            width: 25%;
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #dbeafe; color: #1e40af; }
        .status-preparing { background: #ede9fe; color: #5b21b6; }
        .status-ready { background: #cffafe; color: #0e7490; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #333;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 11px;
            color: #888;
        }
        
        .transaction-image {
            margin: 10px 0;
            text-align: center;
        }
        
        .transaction-image img {
            max-width: 100%;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .print-btn {
            display: block;
            width: 100%;
            max-width: 500px;
            padding: 12px;
            margin-top: 20px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }
        
        .print-btn:hover {
            background: #4338ca;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            margin-top: 10px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .back-btn:hover {
            background: #4b5563;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
                max-width: 100%;
            }
            .print-btn {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }
            .transaction-image img {
                max-height: 150px;
            }
            .back-btn {
                display: none !important;
            }
        }
        
        @media (max-width: 600px) {
            .receipt {
                padding: 20px;
            }
            .row {
                font-size: 12px;
            }
            .items-table td {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt" id="receipt">
        <!-- Receipt Header -->
        <div class="receipt-header">
            <div class="logo">🍔 FOODIE</div>
            <div class="subtitle">Campus Food Ordering System</div>
            <div class="order-id">Order #<?php echo sprintf('%06d', $orderId); ?></div>
        </div>
        
        <!-- Order Info -->
        <div class="row">
            <span class="label">Date</span>
            <span><?php echo $order->getOrderDate()->format('F d, Y h:i A'); ?></span>
        </div>
        <div class="row">
            <span class="label">Status</span>
            <span>
                <span class="status-badge status-<?php echo strtolower($statusName); ?>">
                    <?php echo ucfirst($statusName); ?>
                </span>
            </span>
        </div>
        <div class="row">
            <span class="label">Payment Method</span>
            <span><?php echo htmlspecialchars($paymentMethod); ?></span>
        </div>
        <?php if (!$isCOD): ?>
            <div class="row">
                <span class="label">Account Name</span>
                <span><?php echo htmlspecialchars($orderData['payment_account_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="row">
                <span class="label">Account Number</span>
                <span><?php echo htmlspecialchars($orderData['payment_account_number'] ?? 'N/A'); ?></span>
            </div>
            <?php if (!empty($orderData['transaction_no'])): ?>
            <div class="row">
                <span class="label">Transaction No</span>
                <span><?php echo htmlspecialchars($orderData['transaction_no']); ?></span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="divider"></div>
        
        <!-- Customer Info -->
        <div class="row">
            <span class="label">Customer</span>
            <span><?php echo htmlspecialchars($order->getCustomerName() ?? 'Unknown'); ?></span>
        </div>
        <div class="row">
            <span class="label">Phone</span>
            <span><?php echo htmlspecialchars($order->getCustomerPhone() ?? 'N/A'); ?></span>
        </div>
        <div class="row">
            <span class="label">Delivery Address</span>
            <span style="text-align: right; max-width: 60%;"><?php echo htmlspecialchars($order->getDeliveryAddress() ?? 'N/A'); ?></span>
        </div>
        
        <div class="divider"></div>
        
        <!-- Order Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></td>
                    <td class="item-qty"><?php echo $item['quantity']; ?></td>
                    <td class="item-price">$<?php echo number_format((float)$item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Total -->
        <div class="row total">
            <span>TOTAL</span>
            <span class="amount">$<?php echo number_format($order->getTotalAmount(), 2); ?></span>
        </div>
        
        <!-- Transaction Image -->
        <!-- <?php if ($transactionImage && !$isCOD): ?>
        <div class="transaction-image">
            <div class="divider"></div>
            <p style="font-size: 11px; color: #666; margin-bottom: 5px;">Payment Confirmation</p>
            <img src="/Campus-Food-Ordering-System/Public/<?php echo htmlspecialchars($transactionImage); ?>" 
                 alt="Transaction Image"
                 onerror="this.style.display='none'; this.parentElement.innerHTML='<p style=\'color:#999;font-size:12px;\'>No image available</p>';">
        </div> -->
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <p>Thank you for your order!</p>
            <p style="font-size: 10px; margin-top: 4px;">
                <?php echo date('Y'); ?> &copy; Foodie - Campus Food Ordering System
            </p>
        </div>
    </div>
    
    <!-- Buttons -->
    <div class="no-print" style="width: 100%; max-width: 500px;">
        <button class="print-btn" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Receipt
        </button>
        <!-- <div style="text-align: center; margin-top: 10px;">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </a>
        </div> -->
    </div>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        // Keyboard shortcut: Ctrl+P works natively
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                // Let the browser handle print
                return true;
            }
        });
    </script>
</body>
</html>