<?php
namespace App\Payment\Infrastructure\Repositories;

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

    public function getActivePaymentMethods(): array
    {
        try {
            $sql = "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Error fetching all payment methods: ' . $e->getMessage());
            return [];
        }
    }

    public function addPaymentMethod(string $name, string $accountName, string $accountNumber): int
    {
        $sql = "INSERT INTO payment_methods (method_name, account_name, account_number, is_active) 
                VALUES (:method_name, :account_name, :account_number, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':method_name' => $name,
            ':account_name' => $accountName,
            ':account_number' => $accountNumber
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updatePaymentMethod(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['method_name'])) {
            $fields[] = "method_name = :method_name";
            $params[':method_name'] = $data['method_name'];
        }
        if (isset($data['account_name'])) {
            $fields[] = "account_name = :account_name";
            $params[':account_name'] = $data['account_name'];
        }
        if (isset($data['account_number'])) {
            $fields[] = "account_number = :account_number";
            $params[':account_number'] = $data['account_number'];
        }
        if (isset($data['is_active'])) {
            $fields[] = "is_active = :is_active";
            $params[':is_active'] = $data['is_active'] ? 1 : 0;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE payment_methods SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deletePaymentMethod(int $id): bool
    {
        $sql = "DELETE FROM payment_methods WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}