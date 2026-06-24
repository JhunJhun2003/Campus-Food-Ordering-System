<?php
namespace App\User\Domain\Entities;

class User
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $password;
    private ?string $phone;
    private string $role;
    private \DateTime $createdAt;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $password,
        ?string $phone = null,
        string $role = 'user'  // ✅ Default is 'user'
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->role = $role;
        $this->createdAt = new \DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getPhone(): ?string { return $this->phone; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    // Business Methods - Updated
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isUser(): bool { return $this->role === 'user'; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}