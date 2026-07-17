<?php
declare(strict_types=1);

namespace App\Notification\Domain\Entities;

use App\Notification\Domain\Enums\NotificationType;
use DateTimeImmutable;

class Notification
{
    private int $id;
    private int $userId;
    private string $title;
    private string $message;
    private NotificationType $type;
    private ?string $referenceType;
    private ?int $referenceId;
    private bool $isRead;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        int $userId,
        string $title,
        string $message,
        NotificationType $type = NotificationType::SYSTEM,
        ?string $referenceType = null,
        ?int $referenceId = null,
        bool $isRead = false
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->isRead = $isRead;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
    }

    // ============================================
    // GETTERS
    // ============================================

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): string { return $this->message; }
    public function getType(): NotificationType { return $this->type; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }

    // ============================================
    // SETTERS
    // ============================================

    public function setId(int $id): void { $this->id = $id; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function setMessage(string $message): void { $this->message = $message; }
    public function setType(NotificationType $type): void { $this->type = $type; }
    public function setCreatedAt(DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void { $this->updatedAt = $updatedAt; }

    // ============================================
    // BUSINESS METHODS
    // ============================================

    public function markAsRead(): void
    {
        $this->isRead = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsUnread(): void
    {
        $this->isRead = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isUnread(): bool
    {
        return !$this->isRead;
    }

    public function getTimeAgo(): string
    {
        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->createdAt->getTimestamp();

        if ($diff < 60) {
            return 'Just now';
        }

        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        }

        return $this->createdAt->format('M d, Y');
    }

    // ============================================
    // CONVERSION
    // ============================================

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type->value,
            'type_label' => $this->getTypeLabel(),
            'icon' => $this->type->getIcon(),
            'color' => $this->type->getColor(),
            'bg_class' => $this->type->getBgClass(),
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'is_read' => $this->isRead,
            'time_ago' => $this->getTimeAgo(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }

    private function getTypeLabel(): string
    {
        return match($this->type) {
            NotificationType::ORDER_CREATED => 'Order Created',
            NotificationType::ORDER_ACCEPTED => 'Order Accepted',
            NotificationType::ORDER_PREPARING => 'Order Being Prepared',
            NotificationType::ORDER_READY => 'Order Ready',
            NotificationType::ORDER_COMPLETED => 'Order Completed',
            NotificationType::ORDER_CANCELLED => 'Order Cancelled',
            NotificationType::PAYMENT_RECEIVED => 'Payment Received',
            NotificationType::PAYMENT_FAILED => 'Payment Failed',
            NotificationType::REFUND_REQUESTED => 'Refund Requested',
            NotificationType::REFUND_APPROVED => 'Refund Approved',
            NotificationType::REFUND_REJECTED => 'Refund Rejected',
            NotificationType::WELCOME => 'Welcome',
            NotificationType::EMAIL_VERIFIED => 'Email Verified',
            NotificationType::PASSWORD_CHANGED => 'Password Changed',
            NotificationType::SYSTEM => 'System',
            NotificationType::MAINTENANCE => 'Maintenance',
        };
    }
}