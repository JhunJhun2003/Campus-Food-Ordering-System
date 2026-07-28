<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class VerifyResetCodeRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $code
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($this->code) || strlen($this->code) !== 4 || !ctype_digit($this->code)) {
            $errors['code'] = 'Please enter a valid 4-digit code';
        }

        return $errors;
    }
}