<?php
declare(strict_types=1);

namespace App\Notification\Application\Services;

use App\Notification\Application\Usecases\CreateNotificationUseCase;
use App\Notification\Infrastructure\Repositories\NotificationRepository;

class NotificationDispatcher
{
    private static ?NotificationService $service = null;

    private static function service(): NotificationService
    {
        if (self::$service === null) {
            $repository = new NotificationRepository();
            self::$service = new NotificationService(new CreateNotificationUseCase($repository));
        }

        return self::$service;
    }

    public static function orderStatus(int $userId, int $orderId, int $statusId): void
    {
        $statusMap = [
            1 => 'created',
            2 => 'accepted',
            3 => 'preparing',
            4 => 'ready',
            5 => 'completed',
            6 => 'cancelled',
        ];

        if (!isset($statusMap[$statusId])) {
            return;
        }

        try {
            self::service()->orderNotification(
                $userId,
                $orderId,
                $statusMap[$statusId],
                (string) $orderId
            );
        } catch (\Throwable $e) {
            error_log('Failed to send order notification: ' . $e->getMessage());
        }
    }

    public static function refundStatus(int $userId, int $refundId, string $status): void
    {
        try {
            self::service()->refundNotification($userId, $refundId, $status);
        } catch (\Throwable $e) {
            error_log('Failed to send refund notification: ' . $e->getMessage());
        }
    }
}
