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