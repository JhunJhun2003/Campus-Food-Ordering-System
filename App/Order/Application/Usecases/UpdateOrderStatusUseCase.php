<?php
namespace App\Order\Application\Usecases;

use App\Order\Domain\Repositories\OrderRepositoryInterface;
use Inc\Database;

class UpdateOrderStatusUseCase
{
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(int $orderId, int $statusId): array
    {
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - Order status and related operations
            $db->beginTransaction();
            
            // Check if order exists
            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                throw new \Exception('Order not found.');
            }
            
            $oldStatusId = $order->getStatusId();
            
            // Update order status
            $this->orderRepository->updateStatus($orderId, $statusId);
            
            // If order is completed (status 5), update payment status
            if ($statusId === 5) { // Completed
                $this->updatePaymentStatus($orderId, 2); // Paid
            }
            
            // If order is cancelled (status 6), restore stock
            if ($statusId === 6) { // Cancelled
                $this->updatePaymentStatus($orderId, 3); // Failed
                $this->restoreStock($orderId);
            }
            
            // ✅ All operations succeeded
            $db->commit();
            
            return [
                'success' => true,
                'message' => 'Order status updated successfully'
            ];
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage()
            ];
        }
    }

    private function updatePaymentStatus(int $orderId, int $statusId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE payments SET payment_status_id = :status_id WHERE order_id = :order_id");
        $stmt->execute([
            ':status_id' => $statusId,
            ':order_id' => $orderId
        ]);
    }

    private function restoreStock(int $orderId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE foods f
            JOIN order_items oi ON f.id = oi.food_id
            SET f.stock = f.stock + oi.quantity
            WHERE oi.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $orderId]);
    }
}