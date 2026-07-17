<?php
declare(strict_types=1);

namespace App\Notification\Domain\Repositories;

use App\Notification\Domain\Entities\Notification;
use App\Notification\Domain\Enums\NotificationType;

interface NotificationRepositoryInterface
{
    public function create(Notification $notification): int;
    public function getByUser(int $userId, int $limit = 20, int $offset = 0): array;
    public function getUnreadByUser(int $userId): array;
    public function findById(int $id): ?Notification;
    public function markAsRead(int $id): bool;
    public function markAllAsRead(int $userId): bool;
    public function delete(int $id): bool;
    public function deleteAllForUser(int $userId): bool;
    public function unreadCount(int $userId): int;
    public function getByType(int $userId, NotificationType $type): array;
    public function getByReference(int $userId, string $referenceType, int $referenceId): array;
}
