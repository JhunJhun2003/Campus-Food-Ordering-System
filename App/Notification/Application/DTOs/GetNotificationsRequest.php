<?php
declare(strict_types=1);

namespace App\Notification\Application\DTOs;

class GetNotificationsRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?bool $unreadOnly = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if ($this->userId <= 0) {
            $errors['user_id'] = 'Invalid user ID';
        }

        if ($this->limit <= 0 || $this->limit > 100) {
            $errors['limit'] = 'Limit must be between 1 and 100';
        }

        if ($this->offset < 0) {
            $errors['offset'] = 'Offset cannot be negative';
        }

        return $errors;
    }
}
