<?php
declare(strict_types=1);

namespace App\Notification\Application\Usecases;

use App\Notification\Domain\Repositories\NotificationRepositoryInterface;

class MarkAllAsReadUseCase
{
    private NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(int $userId): array
    {
        try {
            $result = $this->notificationRepository->markAllAsRead($userId);

            if ($result) {
                return ['success' => true, 'message' => 'All notifications marked as read'];
            }

            return ['success' => false, 'message' => 'No unread notifications to mark'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to mark all notifications as read: ' . $e->getMessage()];
        }
    }
}