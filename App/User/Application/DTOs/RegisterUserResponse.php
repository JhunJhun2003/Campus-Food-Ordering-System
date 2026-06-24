<?php
namespace App\User\Application\DTOs;

use App\User\Domain\Entities\User;

class RegisterUserResponse
{
    public bool $success;
    public string $message;
    public ?User $user;
    public ?array $errors;

    public function __construct(
        bool $success,
        string $message,
        ?User $user = null,
        ?array $errors = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->user = $user;
        $this->errors = $errors;
    }
}