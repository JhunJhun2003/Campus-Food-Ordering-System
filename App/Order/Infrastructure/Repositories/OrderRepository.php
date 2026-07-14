<?php
namespace App\Order\Infrastructure\Repositories;

use App\Order\Domain\Entities\Order;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use Inc\Database;
use PDO;
use DateTime;

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
        $columns = ['user_id', 'status_id', 'total_amount', 'order_date'];
        $values = [':user_id', ':status_id', ':total_amount', 'NOW()'];
        $params = [
            ':user_id' => $order->getUserId(),
            ':status_id' => $order->getStatusId(),
            ':total_amount' => $order->getTotalAmount()
        ];

        $optionalColumns = [
            'delivery_address' => $order->getDeliveryAddress(),
            'payment_method' => $order->getPaymentMethod(),
            'customer_name' => $order->getCustomerName(),
            'customer_phone' => $order->getCustomerPhone(),
            'account_name' => $order->getAccountName(),
            'account_number' => $order->getAccountNumber(),
            'transaction_image' => $order->getTransactionImage()
        ];

        foreach ($optionalColumns as $column => $value) {
            if ($this->orderHasColumn($column)) {
                $param = ':' . $column;
                $columns[] = $column;
                $values[] = $param;
                $params[$param] = $value;
            }
        }

       $sql = "INSERT INTO orders (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $values) . ")"; 

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
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
        $sql = "SELECT o.*, 
                {$this->customerNameSelect()} as customer_name, 
                {$this->customerPhoneSelect()} as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    /**
     * Find order by ID with payment details (for staff/order details view)
     */
    /**
 * Find order by ID with payment details
 */
public function findByIdWithDetails(int $id): ?array
{
    $sql = "SELECT 
                o.*,
                os.status_name,
                u.name as customer_name_from_user,
                u.phone as customer_phone_from_user,
                p.id as payment_id,
                p.payment_method_id,
                p.payment_status_id,
                p.amount as payment_amount,
                p.transaction_no,
                p.transaction_image,  -- ✅ Add this
                p.payment_date,
                pm.method_name as payment_method_name,
                pm.account_name as payment_account_name,
                pm.account_number as payment_account_number,
                ps.status_name as payment_status_name
            FROM orders o
            LEFT JOIN order_statuses os ON o.status_id = os.id
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN payments p ON o.id = p.order_id
            LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
            LEFT JOIN payment_statuses ps ON p.payment_status_id = ps.id
            WHERE o.id = :id";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        return null;
    }
    
    // If no payment record exists, assume Cash on Delivery
    if ($order['payment_method_name'] === null) {
        $order['payment_method_name'] = 'Cash on Delivery';
        $order['payment_account_name'] = null;
        $order['payment_account_number'] = null;
        $order['payment_status_name'] = 'pending';
        $order['payment_amount'] = $order['total_amount'];
        $order['payment_method_id'] = 1;
        $order['transaction_image'] = null;  // ✅ No image for COD
    }
    
    // Get order items
    $order['items'] = $this->getOrderItems($id);
    
    return $order;
}

    public function findAll(): array
    {
        $sql = "SELECT o.*, 
                {$this->customerNameSelect()} as customer_name, 
                {$this->customerPhoneSelect()} as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.order_date DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
    }

    public function findByStatus(int $statusId): array
    {
        $sql = "SELECT o.*, 
                {$this->customerNameSelect()} as customer_name, 
                {$this->customerPhoneSelect()} as customer_phone 
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
                    {$this->customerNameSelect()} as customer_name,
                    {$this->customerPhoneSelect()} as customer_phone
                FROM orders o
                JOIN order_statuses os ON o.status_id = os.id
                JOIN users u ON o.user_id = u.id
                WHERE o.user_id = :user_id
                ORDER BY o.order_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $sql = "SELECT o.*, 
                {$this->customerNameSelect()} as customer_name, 
                {$this->customerPhoneSelect()} as customer_phone, 
                os.status_name 
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

    public function saveItems(int $orderId, array $items): void
    {
        $sql = "DELETE FROM order_items WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);

        $sql = "INSERT INTO order_items (order_id, food_id, quantity, unit_price, subtotal) 
                VALUES (:order_id, :food_id, :quantity, :unit_price, :subtotal)";
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                ':order_id' => $orderId,
                ':food_id' => $item['food_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':subtotal' => $item['quantity'] * $item['unit_price']
            ]);
        }
    }

    // ============================================
    // STATUS METHODS
    // ============================================

    public function getOrderStatuses(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM order_statuses
            ORDER BY id
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getOrderStatusById(int $statusId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM order_statuses
            WHERE id = :id
        ");
        $stmt->execute([':id' => $statusId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
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

    public function getPreparingOrders(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE status_id = 3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getCompletedOrders(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE status_id = 5");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function countByStatusName(string $statusName): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM orders o
            JOIN order_statuses os ON o.status_id = os.id
            WHERE LOWER(os.status_name) = LOWER(:status_name)
        ");
        $stmt->execute([':status_name' => $statusName]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function getTotalRevenue(): float
    {
        $stmt = $this->db->query("
            SELECT SUM(total_amount) as total
            FROM orders
            WHERE status_id IN (
                SELECT id FROM order_statuses
                WHERE status_name IN ('completed', 'ready')
            )
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total'] ?? 0);
    }

    public function getMonthlyRevenue(int $months = 6): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(order_date, '%Y-%m') as month,
                    SUM(total_amount) as revenue,
                    COUNT(*) as order_count
                FROM orders 
                WHERE status_id = 5
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC
                LIMIT :months";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':months', $months, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        
        foreach (array_reverse($results) as $row) {
            $monthNum = (int) substr($row['month'], 5);
            $chartData[] = [
                'month' => $monthNames[$monthNum - 1] ?? $row['month'],
                'revenue' => (float) $row['revenue'],
                'orders' => (int) $row['order_count']
            ];
        }
        
        return $chartData;
    }

    public function getOrderStats(): array
    {
        $sql = "SELECT 
                    os.id as status_id,
                    os.status_name,
                    COUNT(o.id) as count,
                    COALESCE(SUM(o.total_amount), 0) as total
                FROM order_statuses os
                LEFT JOIN orders o ON o.status_id = os.id
                GROUP BY os.id, os.status_name
                ORDER BY os.id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderStatistics(): array
    {
        return [
            'total' => $this->getTotalOrders(),
            'pending' => $this->getPendingOrders(),
            'preparing' => $this->countByStatusName('preparing'),
            'ready' => $this->countByStatusName('ready'),
            'completed' => $this->getCompletedOrders(),
            'cancelled' => $this->countByStatusName('cancelled'),
            'today' => $this->countToday(),
            'monthly' => $this->countMonthly(),
            'revenue' => $this->getTotalRevenue()
        ];
    }

    public function countToday(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM orders
            WHERE DATE(order_date) = CURDATE()
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['count'] ?? 0);
    }

    public function countMonthly(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count
            FROM orders
            WHERE MONTH(order_date) = MONTH(CURDATE())
            AND YEAR(order_date) = YEAR(CURDATE())
        ");
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
            $data['delivery_address'] ?? null,
            $data['payment_method'] ?? null,
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['account_name'] ?? null,
            $data['account_number'] ?? null,
            $data['transaction_image'] ?? null,
            !empty($data['order_date']) ? new DateTime($data['order_date']) : null
        );
    }

    private function customerNameSelect(): string
    {
        return $this->orderHasColumn('customer_name')
            ? 'COALESCE(NULLIF(o.customer_name, ""), u.name)'
            : 'u.name';
    }

    private function customerPhoneSelect(): string
    {
        return $this->orderHasColumn('customer_phone')
            ? 'COALESCE(NULLIF(o.customer_phone, ""), u.phone)'
            : 'u.phone';
    }

    private function orderHasColumn(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $stmt = $this->db->query('SHOW COLUMNS FROM orders');
            $columns = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field'));
        }

        return isset($columns[$column]);
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

        /**
     * Lock order for update (pessimistic locking)
     * Must be called inside a transaction
     */
    public function lockOrder(int $orderId): ?array
    {
        try {
            $sql = "SELECT o.*, 
                           {$this->customerNameSelect()} as customer_name, 
                           {$this->customerPhoneSelect()} as customer_phone,
                           os.status_name
                    FROM orders o
                    LEFT JOIN order_statuses os ON o.status_id = os.id
                    JOIN users u ON o.user_id = u.id
                    WHERE o.id = :id 
                    FOR UPDATE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $orderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (\PDOException $e) {
            error_log('Error locking order: ' . $e->getMessage());
            throw $e;
        }
    }

}