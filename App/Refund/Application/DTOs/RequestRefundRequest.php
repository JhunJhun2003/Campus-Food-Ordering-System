<?php
declare(strict_types=1);

namespace App\Refund\Application\DTOs;

class RequestRefundRequest
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $userId,
        public readonly string $reason
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (empty($this->orderId) || $this->orderId <= 0) {
            $errors['order_id'] = 'Invalid order ID';
        }

        if (empty($this->userId) || $this->userId <= 0) {
            $errors['user_id'] = 'Invalid user ID';
        }

        if (empty($this->reason) || strlen($this->reason) < 5) {
            $errors['reason'] = 'Please provide a reason (minimum 5 characters)';
        }

        if (strlen($this->reason) > 500) {
            $errors['reason'] = 'Reason is too long (maximum 500 characters)';
        }

        return $errors;
    }

    /**
     * Create DTO from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: (int) ($data['order_id'] ?? 0),
            userId: (int) ($data['user_id'] ?? 0),
            reason: (string) ($data['reason'] ?? '')
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'reason' => $this->reason
        ];
    }
}