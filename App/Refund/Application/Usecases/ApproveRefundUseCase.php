<?php
declare(strict_types=1);

namespace App\Refund\Application\Usecases;

use App\Refund\Domain\Repositories\RefundRepositoryInterface;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Refund\Application\DTOs\ApproveRefundRequest;
use App\Notification\Application\Services\NotificationDispatcher;
use Inc\Database;

class ApproveRefundUseCase
{
    private RefundRepositoryInterface $refundRepository;
    private OrderRepositoryInterface $orderRepository;
    private PaymentRepositoryInterface $paymentRepository;
    private FoodRepositoryInterface $foodRepository;

    public function __construct(
        RefundRepositoryInterface $refundRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentRepositoryInterface $paymentRepository,
        FoodRepositoryInterface $foodRepository
    ) {
        $this->refundRepository = $refundRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->foodRepository = $foodRepository;
    }

    public function execute(ApproveRefundRequest $request): array
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

            // 5. Lock order
            $order = $this->orderRepository->lockOrder($refund->getOrderId());
            if (!$order) {
                throw new \Exception('Order not found');
            }

            // 6. Lock payment using the new method
            $payment = $this->paymentRepository->lockPayment($refund->getPaymentId());
            if (!$payment) {
                throw new \Exception('Payment not found');
            }

            // 7. Approve refund
            $refund->approve($request->adminId, $request->notes);
            $this->refundRepository->save($refund);

            // 8. Update payment status to failed (status_id = 3)
            // The database schema only allows payment statuses 1=pending, 2=paid, 3=failed.
            $this->paymentRepository->updateStatus($refund->getPaymentId(), 3);

            // 9. Update order status to cancelled (status_id = 6)
            $this->orderRepository->updateStatus($refund->getOrderId(), 6);

            // 10. Restore stock
            $orderItems = $this->orderRepository->getOrderItems($refund->getOrderId());
            foreach ($orderItems as $item) {
                $this->foodRepository->restoreStockWithLock($item['food_id'], $item['quantity']);
            }

            $db->commit();

            NotificationDispatcher::refundStatus($order->getUserId(), $request->refundId, 'approved');

            return [
                'success' => true,
                'message' => 'Refund approved successfully'
            ];

        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to approve refund: ' . $e->getMessage()];
        }
    }
}