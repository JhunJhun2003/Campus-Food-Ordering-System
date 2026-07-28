<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class ResetPasswordRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $code,
        public readonly string $newPassword,
        public readonly string $confirmPassword
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

        if (empty($this->newPassword) || strlen($this->newPassword) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }

        if ($this->newPassword !== $this->confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return $errors;
    }
}