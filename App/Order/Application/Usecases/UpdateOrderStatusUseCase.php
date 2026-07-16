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
        $stmt = $db->prepare("SELECT oi.food_id, oi.quantity, oi.food_size_id FROM order_items oi WHERE oi.order_id = :order_id");
        $stmt->execute([':order_id' => $orderId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            if (!empty($item['food_size_id'])) {
                $sizeStmt = $db->prepare("UPDATE food_sizes SET stock = stock + :quantity WHERE id = :size_id");
                $sizeStmt->execute([
                    ':quantity' => (int) $item['quantity'],
                    ':size_id' => (int) $item['food_size_id']
                ]);
            } else {
                $sizeStmt = $db->prepare("SELECT id FROM food_sizes WHERE food_id = :food_id AND is_default = 1 LIMIT 1");
                $sizeStmt->execute([':food_id' => (int) $item['food_id']]);
                $size = $sizeStmt->fetch(\PDO::FETCH_ASSOC);

                if ($size) {
                    $restoreStmt = $db->prepare("UPDATE food_sizes SET stock = stock + :quantity WHERE id = :size_id");
                    $restoreStmt->execute([
                        ':quantity' => (int) $item['quantity'],
                        ':size_id' => (int) $size['id']
                    ]);
                }
            }
        }
    }
}