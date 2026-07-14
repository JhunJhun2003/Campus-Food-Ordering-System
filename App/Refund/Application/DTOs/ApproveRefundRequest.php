<?php
declare(strict_types=1);

namespace App\Refund\Application\DTOs;

class ApproveRefundRequest
{
    public function __construct(
        public readonly int $refundId,
        public readonly int $adminId,
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
            notes: isset($data['notes']) ? (string) $data['notes'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'refund_id' => $this->refundId,
            'admin_id' => $this->adminId,
            'notes' => $this->notes
        ];
    }
}