<?php
declare(strict_types=1);

namespace App\Notification\Application\Usecases;

use App\Notification\Domain\Repositories\NotificationRepositoryInterface;

class DeleteNotificationUseCase
{
    private NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(int $notificationId, int $userId): array
    {
        try {
            $notification = $this->notificationRepository->findById($notificationId);
            if (!$notification) {
                return ['success' => false, 'message' => 'Notification not found'];
            }

            if ($notification->getUserId() !== $userId) {
                return ['success' => false, 'message' => 'You do not own this notification'];
            }

            $result = $this->notificationRepository->delete($notificationId);

            if ($result) {
                return ['success' => true, 'message' => 'Notification deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete notification'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete notification: ' . $e->getMessage()];
        }
    }
}