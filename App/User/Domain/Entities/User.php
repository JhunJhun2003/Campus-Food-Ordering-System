<?php
namespace App\User\Domain\Entities;

use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use DateTime;

class User
{
    private UserId $id;
    private int $roleId;
    private string $roleName;
    private string $name;
    private Email $email;
    private Password $password;
    private ?string $phone;
    private ?string $address;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private bool $isVerified;  // ✅ ADD THIS
    private ?DateTime $emailVerifiedAt;  // ✅ ADD THIS

    public function __construct(
        UserId $id,
        int $roleId,
        string $roleName,
        string $name,
        Email $email,
        Password $password,
        ?string $phone = null,
        ?string $address = null,
        bool $isVerified = false,  // ✅ ADD THIS
        ?DateTime $emailVerifiedAt = null  // ✅ ADD THIS
    ) {
        $this->id = $id;
        $this->roleId = $roleId;
        $this->roleName = $roleName;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->phone = $phone;
        $this->address = $address;
        $this->isVerified = $isVerified;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): UserId { return $this->id; }
    public function getRoleId(): int { return $this->roleId; }
    public function getRoleName(): string { return $this->roleName; }
    public function getName(): string { return $this->name; }
    public function getEmail(): Email { return $this->email; }
    public function getPassword(): Password { return $this->password; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAddress(): ?string { return $this->address; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function isVerified(): bool { return $this->isVerified; }  // ✅ ADD THIS
    public function getEmailVerifiedAt(): ?DateTime { return $this->emailVerifiedAt; }  // ✅ ADD THIS

    // Role Check Methods
    public function isAdmin(): bool { return $this->roleName === 'admin'; }
    public function isStaff(): bool { return $this->roleName === 'staff'; }
    public function isUser(): bool { return $this->roleName === 'user'; }
    public function hasStaffAccess(): bool { 
        return in_array($this->roleName, ['admin', 'staff']); 
    }

    // Business Methods
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

    public function changeAddress(?string $address): void
    {
        $this->address = $address;
        $this->updatedAt = new DateTime();
    }

    public function changePassword(Password $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTime();
    }

    public function changeRole(int $roleId, string $roleName): void
    {
        $this->roleId = $roleId;
        $this->roleName = $roleName;
        $this->updatedAt = new DateTime();
    }

    public function markAsVerified(): void  // ✅ ADD THIS
    {
        $this->isVerified = true;
        $this->emailVerifiedAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'role_id' => $this->roleId,
            'role_name' => $this->roleName,
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'phone' => $this->phone,
            'address' => $this->address,
            'is_verified' => $this->isVerified,
            'email_verified_at' => $this->emailVerifiedAt ? $this->emailVerifiedAt->format('Y-m-d H:i:s') : null,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}