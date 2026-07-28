<?php
declare(strict_types=1);

namespace App\User\Application\DTOs;

class ForgotPasswordRequest
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $captchaToken = null
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (empty($this->email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($this->captchaToken)) {
            $errors['captcha'] = 'Please complete the reCAPTCHA verification';
        }

        return $errors;
    }
}