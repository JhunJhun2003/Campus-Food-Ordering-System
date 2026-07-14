<?php
declare(strict_types=1);

namespace App\Refund\Infrastructure\Repositories;

use App\Refund\Domain\Entities\Refund;
use App\Refund\Domain\Repositories\RefundRepositoryInterface;
use Inc\Database;
use PDO;

class RefundRepository implements RefundRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(Refund $refund): int
    {
        if ($refund->getId() === null) {
            $sql = "INSERT INTO refunds (
                        order_id, payment_id, requested_by, reason, 
                        refund_status_id, notes, created_at
                    ) VALUES (
                        :order_id, :payment_id, :requested_by, :reason,
                        :refund_status_id, :notes, NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':order_id' => $refund->getOrderId(),
                ':payment_id' => $refund->getPaymentId(),
                ':requested_by' => $refund->getRequestedBy(),
                ':reason' => $refund->getReason(),
                ':refund_status_id' => $refund->getRefundStatusId(),
                ':notes' => $refund->getNotes()
            ]);
            
            return (int) $this->db->lastInsertId();
        } else {
            $sql = "UPDATE refunds SET 
                        approved_by = :approved_by,
                        refund_status_id = :refund_status_id,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $refund->getId(),
                ':approved_by' => $refund->getApprovedBy(),
                ':refund_status_id' => $refund->getRefundStatusId(),
                ':notes' => $refund->getNotes()
            ]);
            
            return $refund->getId();
        }
    }

    public function findById(int $id): ?Refund
    {
        $sql = "SELECT * FROM refunds WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findByOrderId(int $orderId): ?Refund
    {
        $sql = "SELECT * FROM refunds WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findByUser(int $userId): array
    {
        try {
            $sql = "SELECT r.*, 
                           u.name as requested_by_name,
                           u.email as requested_by_email,
                           o.total_amount as order_total,
                           o.status_id as order_status_id,
                           os.status_name as order_status_name,
                           rs.status_name as refund_status_name
                    FROM refunds r
                    LEFT JOIN users u ON r.requested_by = u.id
                    LEFT JOIN orders o ON r.order_id = o.id
                    LEFT JOIN order_statuses os ON o.status_id = os.id
                    LEFT JOIN refund_statuses rs ON r.refund_status_id = rs.id
                    WHERE r.requested_by = :user_id
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log('Error fetching user refunds: ' . $e->getMessage());
            return [];
        }
    }

    public function findAllPending(): array
    {
        $sql = "SELECT r.*, u.name as customer_name, o.total_amount 
                FROM refunds r
                JOIN users u ON r.requested_by = u.id
                JOIN orders o ON r.order_id = o.id
                WHERE r.refund_status_id = 1
                ORDER BY r.created_at DESC";
        $stmt = $this->db->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hydrateWithExtra'], $data);
    }

    public function findAll(?int $statusId = null, ?int $userId = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = "SELECT r.*, 
                           u.name as requested_by_name,
                           u.email as requested_by_email,
                           o.total_amount as order_total,
                           o.status_id as order_status_id,
                           os.status_name as order_status_name,
                           rs.status_name as refund_status_name
                    FROM refunds r
                    LEFT JOIN users u ON r.requested_by = u.id
                    LEFT JOIN orders o ON r.order_id = o.id
                    LEFT JOIN order_statuses os ON o.status_id = os.id
                    LEFT JOIN refund_statuses rs ON r.refund_status_id = rs.id
                    WHERE 1=1";
            
            $params = [];
            
            if ($statusId !== null) {
                $sql .= " AND r.refund_status_id = :status_id";
                $params[':status_id'] = $statusId;
            }
            
            if ($userId !== null) {
                $sql .= " AND r.requested_by = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log('Error fetching refunds: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count refunds with filters
     */
    public function count(?int $statusId = null, ?int $userId = null): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM refunds r WHERE 1=1";
            $params = [];
            
            if ($statusId !== null) {
                $sql .= " AND r.refund_status_id = :status_id";
                $params[':status_id'] = $statusId;
            }
            
            if ($userId !== null) {
                $sql .= " AND r.requested_by = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
            
        } catch (\PDOException $e) {
            error_log('Error counting refunds: ' . $e->getMessage());
            return 0;
        }
    }

    public function updateStatus(int $refundId, int $statusId): bool
    {
        $sql = "UPDATE refunds SET refund_status_id = :status_id, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $refundId,
            ':status_id' => $statusId
        ]);
    }

    public function getRefundStatuses(): array
    {
        $stmt = $this->db->query("SELECT * FROM refund_statuses ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hydrate(array $data): Refund
    {
        return new Refund(
            (int) $data['id'],
            (int) $data['order_id'],
            (int) $data['payment_id'],
            (int) $data['requested_by'],
            $data['reason'],
            (int) $data['refund_status_id'],
            $data['approved_by'] ? (int) $data['approved_by'] : null,
            $data['notes'] ?? null
        );
    }

    private function hydrateWithExtra(array $data): array
    {
        $refund = $this->hydrate($data);
        return array_merge($refund->toArray(), [
            'customer_name' => $data['customer_name'] ?? 'Unknown',
            'total_amount' => $data['total_amount'] ?? 0
        ]);
    }
}