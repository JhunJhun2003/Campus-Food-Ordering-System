<?php
declare(strict_types=1);

/**
 * Refund Helper Functions
 * Include this file where refund features are needed
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Refund\Presentation\Http\Controllers\RefundController;
use App\Refund\Application\Usecases\RequestRefundUseCase;
use App\Refund\Application\Usecases\ApproveRefundUseCase;
use App\Refund\Application\Usecases\RejectRefundUseCase;
use App\Refund\Application\Usecases\GetRefundRequestsUseCase;
use App\Refund\Infrastructure\Repositories\RefundRepository;
use App\Order\Infrastructure\Repositories\OrderRepository;
use App\Payment\Infrastructure\Repositories\PaymentRepository;
use App\Food\Infrastructure\Repositories\FoodRepository;

/**
 * Get Refund Controller with all dependencies injected
 */
function getRefundController(): RefundController
{
    static $instance = null;
    
    if ($instance === null) {
        $refundRepository = new RefundRepository();
        $orderRepository = new OrderRepository();
        $paymentRepository = new PaymentRepository();
        $foodRepository = new FoodRepository();

        $requestRefundUseCase = new RequestRefundUseCase(
            $refundRepository,
            $orderRepository,
            $paymentRepository
        );

        $approveRefundUseCase = new ApproveRefundUseCase(
            $refundRepository,
            $orderRepository,
            $paymentRepository,
            $foodRepository
        );

        $rejectRefundUseCase = new RejectRefundUseCase(
            $refundRepository,
            $orderRepository,
            $paymentRepository
        );

        $getRefundRequestsUseCase = new GetRefundRequestsUseCase(
            $refundRepository
        );

        $instance = new RefundController(
            $requestRefundUseCase,
            $approveRefundUseCase,
            $rejectRefundUseCase,
            $getRefundRequestsUseCase
        );
    }
    
    return $instance;
}

/**
 * Check if order can request a refund
 */
function canRequestRefund(int $statusId): bool
{
    // Only pending (1) and confirmed (2) orders can request refund
    return in_array($statusId, [1, 2]);
}

/**
 * Check if order has a pending refund request
 */
function hasPendingRefund(int $orderId): bool
{
    try {
        $db = \Inc\Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM refunds 
            WHERE order_id = :order_id 
            AND refund_status_id = 1
        ");
        $stmt->execute([':order_id' => $orderId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0) > 0;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get refund status badge HTML
 */
function getRefundStatusBadge(int $statusId): string
{
    $statuses = [
        1 => ['label' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-800'],
        2 => ['label' => 'Approved', 'class' => 'bg-green-100 text-green-800'],
        3 => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-800'],
        4 => ['label' => 'Completed', 'class' => 'bg-blue-100 text-blue-800'],
    ];
    
    $status = $statuses[$statusId] ?? ['label' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'];
    
    return sprintf(
        '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
        $status['class'],
        $status['label']
    );
}