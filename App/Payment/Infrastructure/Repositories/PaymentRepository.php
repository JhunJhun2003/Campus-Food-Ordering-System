<?php
declare(strict_types=1);

namespace App\Payment\Infrastructure\Repositories;

use App\Payment\Domain\Entities\PaymentMethod;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use Inc\Database;
use PDO;

class PaymentRepository implements PaymentRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ============================================
    // READ OPERATIONS
    // ============================================

    public function getActivePaymentMethods(): array
    {
        try {
            $sql = "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id";
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'hydrate'], $data);
        } catch (\PDOException $e) {
            error_log('Error fetching payment methods: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllPaymentMethods(): array
    {
        try {
            $sql = "SELECT * FROM payment_methods ORDER BY id";
            $stmt = $this->db->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'hydrate'], $data);
        } catch (\PDOException $e) {
            error_log('Error fetching all payment methods: ' . $e->getMessage());
            return [];
        }
    }

    public function findById(int $id): ?PaymentMethod
    {
        $sql = "SELECT * FROM payment_methods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findByName(string $name): ?PaymentMethod
    {
        $sql = "SELECT * FROM payment_methods WHERE method_name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $name]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    // ============================================
    // WRITE OPERATIONS
    // ============================================

    public function save(PaymentMethod $paymentMethod): int
    {
        $sql = "INSERT INTO payment_methods (method_name, account_name, account_number, is_active) 
                VALUES (:method_name, :account_name, :account_number, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':method_name' => $paymentMethod->getName(),
            ':account_name' => $paymentMethod->getAccountName(),
            ':account_number' => $paymentMethod->getAccountNumber(),
            ':is_active' => $paymentMethod->isActive() ? 1 : 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    public function update(PaymentMethod $paymentMethod): bool
    {
        $sql = "UPDATE payment_methods SET 
                    method_name = :method_name,
                    account_name = :account_name,
                    account_number = :account_number,
                    is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':method_name' => $paymentMethod->getName(),
            ':account_name' => $paymentMethod->getAccountName(),
            ':account_number' => $paymentMethod->getAccountNumber(),
            ':is_active' => $paymentMethod->isActive() ? 1 : 0,
            ':id' => $paymentMethod->getId()
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM payment_methods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function countPaymentsByMethodId(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM payments WHERE payment_method_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    // ============================================
    // PAYMENT RECORD OPERATIONS (For Refund Module)
    // ============================================

    /**
     * Find payment record by order ID
     */
    public function findByOrderId(int $orderId): ?array
    {
        $sql = "SELECT 
                    p.*,
                    pm.method_name as payment_method_name,
                    pm.account_name as payment_account_name,
                    pm.account_number as payment_account_number,
                    ps.status_name as payment_status_name
                FROM payments p
                LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
                LEFT JOIN payment_statuses ps ON p.payment_status_id = ps.id
                WHERE p.order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find payment by transaction number
     */
    public function findByTransactionNo(string $transactionNo): ?array
    {
        $sql = "SELECT 
                    p.*,
                    pm.method_name as payment_method_name,
                    pm.account_name as payment_account_name,
                    pm.account_number as payment_account_number,
                    ps.status_name as payment_status_name
                FROM payments p
                LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
                LEFT JOIN payment_statuses ps ON p.payment_status_id = ps.id
                WHERE p.transaction_no = :transaction_no";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':transaction_no' => $transactionNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update payment status
     */
    public function updateStatus(int $paymentId, int $statusId): bool
    {
        $sql = "UPDATE payments SET payment_status_id = :status_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $paymentId,
            ':status_id' => $statusId
        ]);
    }

    /**
     * Lock payment for update (pessimistic locking)
     * Must be called inside a transaction
     */
    public function lockPayment(int $paymentId): ?array
    {
        $sql = "SELECT p.*, 
                       pm.method_name as payment_method_name,
                       ps.status_name as payment_status_name
                FROM payments p
                LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
                LEFT JOIN payment_statuses ps ON p.payment_status_id = ps.id
                WHERE p.id = :id 
                FOR UPDATE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $paymentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Create payment record
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO payments (
                    order_id, 
                    payment_method_id, 
                    payment_status_id, 
                    amount, 
                    transaction_no, 
                    transaction_image,
                    idempotency_key,
                    payment_date
                ) VALUES (
                    :order_id, 
                    :payment_method_id, 
                    :payment_status_id, 
                    :amount, 
                    :transaction_no, 
                    :transaction_image, 
                    :idempotency_key, 
                    NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':order_id' => $data['order_id'],
            ':payment_method_id' => $data['payment_method_id'],
            ':payment_status_id' => $data['payment_status_id'],
            ':amount' => $data['amount'],
            ':transaction_no' => $data['transaction_no'] ?? null,
            ':transaction_image' => $data['transaction_image'] ?? null,
            ':idempotency_key' => $data['idempotency_key'] ?? null
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    // ============================================
    // PAYMENT STATUS CONSTANTS
    // ============================================

    public const STATUS_PENDING = 1;
    public const STATUS_PAID = 2;
    public const STATUS_REFUND_PENDING = 3;
    public const STATUS_REFUNDED = 4;
    public const STATUS_FAILED = 5;

    // ============================================
    // HYDRATION
    // ============================================

    private function hydrate(array $data): PaymentMethod
    {
        $paymentMethod = new PaymentMethod(
            (int) $data['id'],
            $data['method_name'],
            $data['account_name'] ?? null,
            $data['account_number'] ?? null,
            (bool) ($data['is_active'] ?? true)
        );

        if (isset($data['created_at'])) {
            $paymentMethod->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (isset($data['updated_at'])) {
            $paymentMethod->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        return $paymentMethod;
    }
}