<?php
namespace App\User\Domain\Entities;

use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use DateTime;

class User
{
    private UserId $id;
    private string $name;
    private Email $email;
    private Password $password;
    private ?string $phone;
    private string $role;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        UserId $id,
        string $name,
        Email $email,
        Password $password,
        ?string $phone = null,
        string $role = 'user'
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->role = $role;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): UserId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): Email { return $this->email; }
    public function getPassword(): Password { return $this->password; }
    public function getPhone(): ?string { return $this->phone; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // Business Methods
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser(): bool { return $this->role === 'user'; }

    public function changeName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTime();
    }

    public function changePhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->updatedAt = new DateTime();
    }

    public function changePassword(Password $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTime();
    }

    public function promoteToAdmin(): void
    {
        $this->role = 'admin';
        $this->updatedAt = new DateTime();
    }

    public function demoteToUser(): void
    {
        $this->role = 'user';
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'phone' => $this->phone,
            'role' => $this->role,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}