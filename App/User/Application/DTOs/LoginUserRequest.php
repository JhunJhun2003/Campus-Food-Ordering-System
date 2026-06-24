<?php
namespace App\User\Application\DTOs;

class LoginUserRequest
{
    public string $email;
    public string $password;
    public bool $remember;

    public function __construct(string $email, string $password, bool $remember = false)
    {
        $this->email = $email;
        $this->password = $password;
        $this->remember = $remember;
    }
}