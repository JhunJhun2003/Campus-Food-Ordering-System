<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class GoogleLoginRequest
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $state = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (empty($this->code)) {
            $errors['code'] = 'Authorization code is required';
        }

        return $errors;
    }
}