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

    public function save(Order $order): void
    {
        if ($order->getId() === null) {
            $sql = "INSERT INTO orders (user_id, status_id, total_amount) 
                    VALUES (:user_id, :status_id, :total_amount)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $order->getUserId(),
                ':status_id' => $order->getStatusId(),
                ':total_amount' => $order->getTotalAmount()
            ]);
        } else {
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
        $sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.user_id = :user_id 
                ORDER BY o.order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrate'], $data);
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
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }

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
}