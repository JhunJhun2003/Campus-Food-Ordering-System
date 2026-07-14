<?php
declare(strict_types=1);

namespace App\Refund\Application\DTOs;

class GetRefundRequestsRequest
{
    public ?int $statusId;
    public ?int $userId;
    public int $limit;
    public int $offset;

    public function __construct(
        ?int $statusId = null,
        ?int $userId = null,
        int $limit = 20,
        int $offset = 0
    ) {
        $this->statusId = $statusId;
        $this->userId = $userId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->limit < 1 || $this->limit > 100) {
            $errors['limit'] = 'Limit must be between 1 and 100';
        }

        if ($this->offset < 0) {
            $errors['offset'] = 'Offset must be 0 or greater';
        }

        return $errors;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['status_id'] ?? null,
            $data['user_id'] ?? null,
            (int) ($data['limit'] ?? 20),
            (int) ($data['offset'] ?? 0)
        );
    }
}