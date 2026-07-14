<?php
declare(strict_types=1);

namespace App\Refund\Application\Usecases;

use App\Refund\Domain\Repositories\RefundRepositoryInterface;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Refund\Application\DTOs\RejectRefundRequest;
use Inc\Database;

class RejectRefundUseCase
{
    private RefundRepositoryInterface $refundRepository;
    private OrderRepositoryInterface $orderRepository;
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(
        RefundRepositoryInterface $refundRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentRepositoryInterface $paymentRepository
    ) {
        $this->refundRepository = $refundRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(RejectRefundRequest $request): array
    {
        $db = Database::getConnection();
        
        try {
            // 1. Validate request
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            // 2. Get refund
            $refund = $this->refundRepository->findById($request->refundId);
            if (!$refund) {
                return ['success' => false, 'message' => 'Refund request not found'];
            }

            // 3. Check if refund is pending
            if (!$refund->isPending()) {
                return ['success' => false, 'message' => 'This refund request is already processed'];
            }

            // 4. Start transaction
            $db->beginTransaction();

            // 5. Reject refund
            $refund->reject($request->adminId, $request->notes);
            $this->refundRepository->save($refund);

            // 6. Update payment status back to paid (status_id = 2)
            $this->paymentRepository->updateStatus($refund->getPaymentId(), 2);

            // 7. Update order status back to confirmed (status_id = 2)
            $this->orderRepository->updateStatus($refund->getOrderId(), 2);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Refund request rejected'
            ];

        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to reject refund: ' . $e->getMessage()];
        }
    }
}