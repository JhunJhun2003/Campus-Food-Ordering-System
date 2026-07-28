<?php
declare(strict_types=1);

namespace App\User\Domain\Entities;

use DateTime;

class PasswordReset
{
    private ?int $id;
    private int $userId;
    private string $email;
    private string $code;
    private bool $isUsed;
    private DateTime $expiresAt;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    private const EXPIRATION_MINUTES = 10;

    public function __construct(
        ?int $id,
        int $userId,
        string $email,
        string $code,
        bool $isUsed = false,
        ?DateTime $expiresAt = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->email = $email;
        $this->code = $code;
        $this->isUsed = $isUsed;
        $this->expiresAt = $expiresAt ?? (new DateTime())->modify('+' . self::EXPIRATION_MINUTES . ' minutes');
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getEmail(): string { return $this->email; }
    public function getCode(): string { return $this->code; }
    public function isUsed(): bool { return $this->isUsed; }
    public function getExpiresAt(): DateTime { return $this->expiresAt; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    public function markAsUsed(): void 
    { 
        $this->isUsed = true; 
        $this->updatedAt = new DateTime();
    }

    public function isValid(): bool
    {
        $now = new DateTime();
        return !$this->isUsed && $this->expiresAt > $now;
    }

    public function isExpired(): bool
    {
        $now = new DateTime();
        return $this->expiresAt <= $now;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'email' => $this->email,
            'code' => $this->code,
            'is_used' => $this->isUsed,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ];
    }
}