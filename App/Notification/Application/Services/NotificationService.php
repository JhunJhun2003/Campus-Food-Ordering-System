<?php
declare(strict_types=1);

namespace App\Notification\Application\Services;

use App\Notification\Domain\Enums\NotificationType;
use App\Notification\Application\DTOs\CreateNotificationRequest;
use App\Notification\Application\Usecases\CreateNotificationUseCase;

class NotificationService
{
    private CreateNotificationUseCase $createNotificationUseCase;

    public function __construct(CreateNotificationUseCase $createNotificationUseCase)
    {
        $this->createNotificationUseCase = $createNotificationUseCase;
    }

    /**
     * Send a notification to a user
     */
    public function notify(
        int $userId,
        string $title,
        string $message,
        NotificationType $type = NotificationType::SYSTEM,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): array {
        $request = new CreateNotificationRequest(
            $userId,
            $title,
            $message,
            $type,
            $referenceType,
            $referenceId
        );

        return $this->createNotificationUseCase->execute($request);
    }

    /**
     * Send order notification
     */
    public function orderNotification(
        int $userId,
        int $orderId,
        string $status,
        string $orderNumber
    ): array {
        $messages = [
            'created' => ['title' => 'Order Received', 'message' => "Your order #{$orderNumber} has been received and is being processed."],
            'accepted' => ['title' => 'Order Accepted', 'message' => "Your order #{$orderNumber} has been accepted by the restaurant."],
            'preparing' => ['title' => 'Order Being Prepared', 'message' => "Your order #{$orderNumber} is now being prepared by our kitchen."],
            'ready' => ['title' => 'Order Ready', 'message' => "Your order #{$orderNumber} is ready for pickup/delivery!"],
            'completed' => ['title' => 'Order Completed', 'message' => "Thank you! Your order #{$orderNumber} has been completed."],
            'cancelled' => ['title' => 'Order Cancelled', 'message' => "Your order #{$orderNumber} has been cancelled."],
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

        return $this->notify(
            $userId,
            $info['title'],
            $info['message'],
            $type,
            'order',
            $orderId
        );
    }

    /**
     * Send refund notification
     */
    public function refundNotification(
        int $userId,
        int $refundId,
        string $status
    ): array {
        $messages = [
            'requested' => ['title' => 'Refund Requested', 'message' => "Your refund request has been submitted and is pending review."],
            'approved' => ['title' => 'Refund Approved', 'message' => "Your refund request has been approved. The amount will be credited to your account."],
            'rejected' => ['title' => 'Refund Rejected', 'message' => "Your refund request has been reviewed and rejected."],
        ];

        $typeMap = [
            'requested' => NotificationType::REFUND_REQUESTED,
            'approved' => NotificationType::REFUND_APPROVED,
            'rejected' => NotificationType::REFUND_REJECTED,
        ];

        $info = $messages[$status] ?? $messages['requested'];
        $type = $typeMap[$status] ?? NotificationType::REFUND_REQUESTED;

        return $this->notify(
            $userId,
            $info['title'],
            $info['message'],
            $type,
            'refund',
            $refundId
        );
    }

    /**
     * Send payment notification
     */
    public function paymentNotification(
        int $userId,
        int $paymentId,
        bool $success = true
    ): array {
        if ($success) {
            return $this->notify(
                $userId,
                'Payment Successful',
                'Your payment has been successfully processed.',
                NotificationType::PAYMENT_RECEIVED,
                'payment',
                $paymentId
            );
        } else {
            return $this->notify(
                $userId,
                'Payment Failed',
                'Your payment could not be processed. Please try again.',
                NotificationType::PAYMENT_FAILED,
                'payment',
                $paymentId
            );
        }
    }
}