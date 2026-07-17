<?php
declare(strict_types=1);

namespace App\Notification\Application\DTOs;

use App\Notification\Domain\Enums\NotificationType;

class CreateNotificationRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $message,
        public readonly NotificationType $type = NotificationType::SYSTEM,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if ($this->userId <= 0) {
            $errors['user_id'] = 'Invalid user ID';
        }

        if (empty($this->title) || strlen($this->title) < 3) {
            $errors['title'] = 'Title must be at least 3 characters';
        }

        if (empty($this->message) || strlen($this->message) < 5) {
            $errors['message'] = 'Message must be at least 5 characters';
        }

        return $errors;
    }
}