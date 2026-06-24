<?php
namespace App\User\Application\DTOs;

use App\User\Domain\Entities\User;

class LoginUserResponse
{
    public bool $success;
    public string $message;
    public ?User $user;
    public ?string $redirectUrl;

    public function __construct(
        bool $success,
        string $message,
        ?User $user = null,
        ?string $redirectUrl = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->user = $user;
        $this->redirectUrl = $redirectUrl;
    }
}