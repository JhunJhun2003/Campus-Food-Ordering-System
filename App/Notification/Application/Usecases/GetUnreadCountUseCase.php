<?php
declare(strict_types=1);

namespace App\Notification\Application\Usecases;

use App\Notification\Domain\Repositories\NotificationRepositoryInterface;

class GetUnreadCountUseCase
{
    private NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(int $userId): array
    {
        try {
            $count = $this->notificationRepository->unreadCount($userId);

            return [
                'success' => true,
                'data' => ['count' => $count]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to get unread count: ' . $e->getMessage()];
        }
    }
}