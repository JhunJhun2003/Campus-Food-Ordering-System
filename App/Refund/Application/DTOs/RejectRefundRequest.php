<?php
declare(strict_types=1);

namespace App\Refund\Application\DTOs;

class RejectRefundRequest
{
    public function __construct(
        public readonly int $refundId,
        public readonly int $adminId,
        public readonly ?string $reason = null,
        public readonly ?string $notes = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (empty($this->refundId) || $this->refundId <= 0) {
            $errors['refund_id'] = 'Invalid refund ID';
        }

        if (empty($this->adminId) || $this->adminId <= 0) {
            $errors['admin_id'] = 'Invalid admin ID';
        }

        if ($this->reason !== null && strlen($this->reason) < 5) {
            $errors['reason'] = 'Rejection reason must be at least 5 characters';
        }

        if ($this->reason !== null && strlen($this->reason) > 500) {
            $errors['reason'] = 'Rejection reason is too long (maximum 500 characters)';
        }

        if ($this->notes !== null && strlen($this->notes) > 500) {
            $errors['notes'] = 'Notes are too long (maximum 500 characters)';
        }

        return $errors;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            refundId: (int) ($data['refund_id'] ?? 0),
            adminId: (int) ($data['admin_id'] ?? 0),
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'refund_id' => $this->refundId,
            'admin_id' => $this->adminId,
            'reason' => $this->reason,
            'notes' => $this->notes
        ];
    }
}