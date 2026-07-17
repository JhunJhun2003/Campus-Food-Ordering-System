<?php
declare(strict_types=1);

namespace App\Notification\Application\Services;

use App\Notification\Application\Usecases\CreateNotificationUseCase;
use App\Notification\Domain\Enums\NotificationType;
use App\Notification\Infrastructure\Repositories\NotificationRepository;
use Inc\Database;
use PDO;

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

        $statusKey = $statusMap[$statusId];

        try {
            self::service()->orderNotification(
                $userId,
                $orderId,
                $statusKey,
                (string) $orderId
            );

            self::sendOrderNotificationToAdminsAndStaff($userId, $orderId, $statusKey, (string) $orderId);
        } catch (\Throwable $e) {
            error_log('Failed to send order notification: ' . $e->getMessage());
        }
    }

    private static function sendOrderNotificationToAdminsAndStaff(int $customerUserId, int $orderId, string $status, string $orderNumber): void
    {
        $recipientIds = self::getAdminAndStaffUserIds();
        if (empty($recipientIds)) {
            return;
        }

        $messages = [
            'created' => ['title' => 'New Order Received', 'message' => "A new order #{$orderNumber} has been placed and needs attention."],
            'accepted' => ['title' => 'Order Accepted', 'message' => "Order #{$orderNumber} has been accepted."],
            'preparing' => ['title' => 'Order Being Prepared', 'message' => "Order #{$orderNumber} is now being prepared."],
            'ready' => ['title' => 'Order Ready', 'message' => "Order #{$orderNumber} is ready for pickup or delivery."],
            'completed' => ['title' => 'Order Completed', 'message' => "Order #{$orderNumber} has been completed."],
            'cancelled' => ['title' => 'Order Cancelled', 'message' => "Order #{$orderNumber} has been cancelled."],
        ];

        $typeMap = [
            'created' => NotificationType::ORDER_CREATED,
            'accepted' => NotificationType::ORDER_ACCEPTED,
            'preparing' => NotificationType::ORDER_PREPARING,
            'ready' => NotificationType::ORDER_READY,
            'completed' => NotificationType::ORDER_COMPLETED,
            'cancelled' => NotificationType::ORDER_CANCELLED,
        ];

        $info = $messages[$status] ?? $messages['created'];
        $type = $typeMap[$status] ?? NotificationType::ORDER_CREATED;

        foreach ($recipientIds as $recipientId) {
            if ((int) $recipientId === $customerUserId) {
                continue;
            }

            self::service()->notify(
                (int) $recipientId,
                $info['title'],
                $info['message'],
                $type,
                'order',
                $orderId
            );
        }
    }

    private static function getAdminAndStaffUserIds(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "SELECT u.id
                 FROM users u
                 JOIN roles r ON u.role_id = r.id
                 WHERE LOWER(r.name) IN ('admin', 'staff')"
            );
            $stmt->execute();

            return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
        } catch (\Throwable $e) {
            error_log('Failed to fetch admin and staff recipients: ' . $e->getMessage());
            return [];
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
