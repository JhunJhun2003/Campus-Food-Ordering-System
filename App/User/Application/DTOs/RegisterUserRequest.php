<?php
namespace App\User\Application\DTOs;

class RegisterUserRequest
{
    public string $name;
    public string $email;
    public string $password;
    public ?string $phone;
    public ?string $captchaToken;

    public function __construct(
        string $name,
        string $email,
        string $password,
        ?string $phone = null,
        ?string $captchaToken = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->captchaToken = $captchaToken;
    }
}