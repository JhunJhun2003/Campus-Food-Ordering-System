<?php
declare(strict_types=1);

namespace App\Notification\Application\Usecases;

use App\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Notification\Application\DTOs\GetNotificationsRequest;

class GetUserNotificationsUseCase
{
    private NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(GetNotificationsRequest $request): array
    {
        try {
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            if ($request->unreadOnly) {
                $notifications = $this->notificationRepository->getUnreadByUser($request->userId);
            } else {
                $notifications = $this->notificationRepository->getByUser(
                    $request->userId,
                    $request->limit,
                    $request->offset
                );
            }

            $unreadCount = $this->notificationRepository->unreadCount($request->userId);

            return [
                'success' => true,
                'data' => array_map(fn($n) => $n->toArray(), $notifications),
                'unread_count' => $unreadCount,
                'total' => count($notifications)
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to get notifications: ' . $e->getMessage()];
        }
    }
}