<?php
declare(strict_types=1);

namespace App\Refund\Domain\Entities;

use DateTime;

class Refund
{
    private ?int $id;
    private int $orderId;
    private int $paymentId;
    private int $requestedBy;
    private ?int $approvedBy;
    private string $reason;
    private int $refundStatusId;
    private ?string $notes;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        int $orderId,
        int $paymentId,
        int $requestedBy,
        string $reason,
        int $refundStatusId = 1, // 1 = pending
        ?int $approvedBy = null,
        ?string $notes = null
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->paymentId = $paymentId;
        $this->requestedBy = $requestedBy;
        $this->approvedBy = $approvedBy;
        $this->reason = $reason;
        $this->refundStatusId = $refundStatusId;
        $this->notes = $notes;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getOrderId(): int { return $this->orderId; }
    public function getPaymentId(): int { return $this->paymentId; }
    public function getRequestedBy(): int { return $this->requestedBy; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function getReason(): string { return $this->reason; }
    public function getRefundStatusId(): int { return $this->refundStatusId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // Business methods
    public function approve(int $adminId, ?string $notes = null): void
    {
        $this->approvedBy = $adminId;
        $this->refundStatusId = 2; // approved
        $this->notes = $notes ?? $this->notes;
        $this->updatedAt = new DateTime();
    }

    public function reject(int $adminId, ?string $notes = null): void
    {
        $this->approvedBy = $adminId;
        $this->refundStatusId = 3; // rejected
        $this->notes = $notes ?? $this->notes;
        $this->updatedAt = new DateTime();
    }

    public function complete(): void
    {
        $this->refundStatusId = 4; // completed
        $this->updatedAt = new DateTime();
    }

    public function isPending(): bool
    {
        return $this->refundStatusId === 1;
    }

    public function isApproved(): bool
    {
        return $this->refundStatusId === 2;
    }

    public function isRejected(): bool
    {
        return $this->refundStatusId === 3;
    }

    public function isCompleted(): bool
    {
        return $this->refundStatusId === 4;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'payment_id' => $this->paymentId,
            'requested_by' => $this->requestedBy,
            'approved_by' => $this->approvedBy,
            'reason' => $this->reason,
            'refund_status_id' => $this->refundStatusId,
            'notes' => $this->notes,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}