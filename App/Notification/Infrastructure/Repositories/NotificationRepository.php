<?php
declare(strict_types=1);

namespace App\Notification\Infrastructure\Repositories;

use App\Notification\Domain\Entities\Notification;
use App\Notification\Domain\Enums\NotificationType;
use App\Notification\Domain\Repositories\NotificationRepositoryInterface;
use Inc\Database;
use PDO;

class NotificationRepository implements NotificationRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(Notification $notification): int
    {
        $sql = "INSERT INTO notifications (
                    user_id, title, message, type, reference_type, reference_id, is_read, created_at
                ) VALUES (
                    :user_id, :title, :message, :type, :reference_type, :reference_id, :is_read, NOW()
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $notification->getUserId(),
            ':title' => $notification->getTitle(),
            ':message' => $notification->getMessage(),
            ':type' => $notification->getType()->value,
            ':reference_type' => $notification->getReferenceType(),
            ':reference_id' => $notification->getReferenceId(),
            ':is_read' => $notification->isRead() ? 1 : 0
        ]);
        
        $id = (int) $this->db->lastInsertId();
        $notification->setId($id);
        return $id;
    }

    public function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $data);
    }

    public function getUnreadByUser(int $userId): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id AND is_read = 0 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $data);
    }

    public function findById(int $id): ?Notification
    {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    public function markAsRead(int $id): bool
    {
        $sql = "UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM notifications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function deleteAllForUser(int $userId): bool
    {
        $sql = "DELETE FROM notifications WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    public function unreadCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getByType(int $userId, NotificationType $type): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id AND type = :type 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type->value
        ]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $data);
    }

    public function getByReference(int $userId, string $referenceType, int $referenceId): array
    {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = :user_id 
                AND reference_type = :reference_type 
                AND reference_id = :reference_id 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':reference_type' => $referenceType,
            ':reference_id' => $referenceId
        ]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $data);
    }

    private function hydrate(array $data): Notification
    {
        $notification = new Notification(
            (int) $data['user_id'],
            $data['title'],
            $data['message'],
            NotificationType::tryFrom($data['type'] ?? '') ?? NotificationType::SYSTEM,
            $data['reference_type'] ?? null,
            isset($data['reference_id']) && $data['reference_id'] !== null ? (int) $data['reference_id'] : null,
            (bool) $data['is_read']
        );

        $notification->setId((int) $data['id']);

        if (!empty($data['created_at'])) {
            $notification->setCreatedAt(new \DateTimeImmutable($data['created_at']));
        }

        if (!empty($data['updated_at'])) {
            $notification->setUpdatedAt(new \DateTimeImmutable($data['updated_at']));
        }

        return $notification;
    }
}