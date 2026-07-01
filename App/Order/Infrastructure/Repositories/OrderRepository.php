<?php
namespace App\Order\Infrastructure\Repositories;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use Inc\Database;
use PDO;

class OrderRepository implements OrderRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ============================================
    // CRUD METHODS
    // ============================================

    public function save(Order $order): int
    {
        $sql = "INSERT INTO orders (user_id, status_id, total_amount, order_date) 
                VALUES (:user_id, :status_id, :total_amount, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $order->getUserId(),
            ':status_id' => $order->getStatusId(),
            ':total_amount' => $order->getTotalAmount()
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function updateOrder(Order $order): void
    {
        $sql = "UPDATE orders SET 
                    status_id = :status_id,
                    total_amount = :total_amount 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status_id' => $order->getStatusId(),
            ':total_amount' => $order->getTotalAmount(),
            ':id' => $order->getId()
        ]);
    }

    public function findById(int $id): ?Order
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.order_date DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findByStatus(int $statusId): array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.status_id = :status_id 
                ORDER BY o.order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status_id' => $statusId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findByUser(int $userId): array
    {
        $sql = "SELECT 
                    o.*,
                    os.status_name,
                    u.name as customer_name,
                    u.phone as customer_phone
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.id
                JOIN users u ON o.user_id = u.id
                WHERE o.user_id = :user_id
                ORDER BY o.order_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get items for each order
        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
            
            $itemNames = [];
            $itemEmojis = [];
            foreach ($order['items'] as $item) {
                $itemNames[] = $item['food_name'];
                $itemEmojis[] = $this->getEmojiForFood($item['food_id']);
            }
            $order['item_names'] = implode(', ', array_slice($itemNames, 0, 2));
            if (count($itemNames) > 2) {
                $order['item_names'] .= ' + ' . (count($itemNames) - 2) . ' more';
            }
            $order['item_emoji'] = $itemEmojis[0] ?? '🍽️';
            $order['total_items'] = array_sum(array_column($order['items'], 'quantity'));
        }

        return $orders;
    }

    public function updateStatus(int $orderId, int $statusId): void
    {
        $sql = "UPDATE orders SET status_id = :status_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status_id' => $statusId,
            ':id' => $orderId
        ]);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM orders WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function getRecentOrders(int $limit = 10): array
    {
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone, os.status_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN order_statuses os ON o.status_id = os.id 
                ORDER BY o.order_date DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ============================================
    // ORDER ITEMS METHODS
    // ============================================

    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT 
                    oi.*,
                    f.name as food_name,
                    f.image
                FROM order_items oi
                JOIN foods f ON oi.food_id = f.id
                WHERE oi.order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem(int $orderId, int $foodId, int $quantity, float $unitPrice): void
    {
        $subtotal = $unitPrice * $quantity;
        
        $sql = "INSERT INTO order_items (order_id, food_id, quantity, unit_price, subtotal) 
                VALUES (:order_id, :food_id, :quantity, :unit_price, :subtotal)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':order_id' => $orderId,
            ':food_id' => $foodId,
            ':quantity' => $quantity,
            ':unit_price' => $unitPrice,
            ':subtotal' => $subtotal
        ]);
    }

    // ============================================
    // STATS METHODS
    // ============================================

    public function getTotalOrders(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getPendingOrders(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status_id = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    private function hydrate(array $data): Order
    {
        return new Order(
            (int) $data['id'],
            (int) $data['user_id'],
            (int) $data['status_id'],
            (float) $data['total_amount'],
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null
        );
    }

    private function getEmojiForFood(int $foodId): string
    {
        $sql = "SELECT category_id FROM foods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $foodId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $categoryId = $result['category_id'] ?? 1;
        
        $emojiMap = [
            1 => '🍔',
            2 => '🍕',
            3 => '🥤',
            4 => '🍰',
            5 => '🍚',
        ];
        
        return $emojiMap[$categoryId] ?? '🍽️';
    }
}