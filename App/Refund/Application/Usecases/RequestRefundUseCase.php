<?php
declare(strict_types=1);

namespace App\Refund\Application\Usecases;

use App\Refund\Domain\Entities\Refund;
use App\Refund\Domain\Repositories\RefundRepositoryInterface;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Refund\Application\DTOs\RequestRefundRequest;
use Inc\Database;

class RequestRefundUseCase
{
    private RefundRepositoryInterface $refundRepository;
    private OrderRepositoryInterface $orderRepository;
    private PaymentRepositoryInterface $paymentRepository;

    // ✅ Allowed statuses for refund request
    private const ALLOWED_STATUSES = [1, 2]; // pending, confirmed

    public function __construct(
        RefundRepositoryInterface $refundRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $this->refundRepository = $refundRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(RequestRefundRequest $request): array
    {
        $db = Database::getConnection();
        
        try {
            // 1. Validate request
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            // 2. Get order
            $order = $this->orderRepository->findById($request->orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            // 3. Check if user owns the order
            if ($order->getUserId() !== $request->userId) {
                return ['success' => false, 'message' => 'You do not own this order'];
            }

            // 4. Check if order status allows refund
            $statusId = $order->getStatusId();
            if (!in_array($statusId, self::ALLOWED_STATUSES)) {
                return [
                    'success' => false, 
                    'message' => 'Refund is only available for pending or confirmed orders'
                ];
            }

            // 5. Check if refund already exists
            $existingRefund = $this->refundRepository->findByOrderId($request->orderId);
            if ($existingRefund && $existingRefund->isPending()) {
                return ['success' => false, 'message' => 'A refund request is already pending for this order'];
            }

            // 6. Get payment
            $payment = $this->paymentRepository->findByOrderId($request->orderId);
            if (!$payment) {
                return ['success' => false, 'message' => 'Payment not found for this order'];
            }

            // 7. Check if payment is paid (status_id = 2)
            if ($payment['payment_status_id'] != 2) {
                return ['success' => false, 'message' => 'This order has not been paid yet'];
            }

            // 8. Start transaction
            $db->beginTransaction();

            // 9. Create refund record
            $refund = new Refund(
                null,
                $request->orderId,
                $payment['id'],
                $request->userId,
                $request->reason,
                1 // pending
            );

            $refundId = $this->refundRepository->save($refund);

            // 10. Update order status to 'refund_requested' (status_id = 7)
            // You may need to add this status to order_statuses table
            $this->orderRepository->updateStatus($request->orderId, 7); // 7 = refund_requested

            // 11. Update payment status to 'failed' (status_id = 3)
            // The schema only supports payment statuses 1=pending, 2=paid, 3=failed.
            $this->paymentRepository->updateStatus($payment['id'], 3);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Refund request submitted successfully',
                'refund_id' => $refundId
            ];

        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to request refund: ' . $e->getMessage()];
        }
    }
}