<?php
declare(strict_types=1);

namespace App\Notification\Application\Usecases;

use App\Notification\Domain\Entities\Notification;
use App\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Notification\Application\DTOs\CreateNotificationRequest;

class CreateNotificationUseCase
{
    private NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function execute(CreateNotificationRequest $request): array
    {
        try {
            $errors = $request->validate();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            }

            $notification = new Notification(
                $request->userId,
                $request->title,
                $request->message,
                $request->type,
                $request->referenceType,
                $request->referenceId
            );

            $id = $this->notificationRepository->create($notification);

            return [
                'success' => true,
                'message' => 'Notification created successfully',
                'data' => ['notification_id' => $id]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create notification: ' . $e->getMessage()];
        }
    }
}