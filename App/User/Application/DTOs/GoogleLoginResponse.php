<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

use App\User\Domain\Entities\User;

class GoogleLoginResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?User $user = null,
        public readonly ?string $redirectUrl = null,
        public readonly array $errors = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'user' => $this->user ? $this->user->toArray() : null,
            'redirect_url' => $this->redirectUrl,
            'errors' => $this->errors
        ];
    }
}