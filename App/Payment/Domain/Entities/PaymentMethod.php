<?php
declare(strict_types=1);

namespace App\Payment\Domain\Entities;

use DateTime;

class PaymentMethod
{
    private ?int $id;
    private string $name;
    private ?string $accountName;
    private ?string $accountNumber;
    private bool $isActive;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int $id,
        string $name,
        ?string $accountName = null,
        ?string $accountNumber = null,
        bool $isActive = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->isActive = $isActive;
        $this->createdAt = new DateTime();
        $this->updatedAt = null;
    }

    // ============================================
    // GETTERS
    // ============================================

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getAccountName(): ?string { return $this->accountName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    // ============================================
    // SETTERS - ✅ ALL ADDED
    // ============================================

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
    }

    public function setAccountName(?string $accountName): void
    {
        $this->accountName = $accountName;
        $this->updatedAt = new DateTime();
    }

    public function setAccountNumber(?string $accountNumber): void
    {
        $this->accountNumber = $accountNumber;
        $this->updatedAt = new DateTime();
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updatedAt = new DateTime();
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    // ============================================
    // BUSINESS METHODS
    // ============================================

    public function isDigital(): bool
    {
        return $this->name !== 'Cash on Delivery';
    }

    public function isCashOnDelivery(): bool
    {
        return $this->name === 'Cash on Delivery';
    }

    public function hasAccountDetails(): bool
    {
        return $this->accountName !== null && $this->accountNumber !== null;
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTime();
    }

    public function update(string $name, ?string $accountName, ?string $accountNumber): void
    {
        $this->name = $name;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->updatedAt = new DateTime();
    }

    public function toggleActive(): void
    {
        $this->isActive = !$this->isActive;
        $this->updatedAt = new DateTime();
    }

    // ============================================
    // VALIDATION
    // ============================================

    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors['name'] = 'Payment method name is required.';
        }

        if ($this->isDigital() && empty($this->accountName)) {
            $errors['account_name'] = 'Account name is required for digital payments.';
        }

        if ($this->isDigital() && empty($this->accountNumber)) {
            $errors['account_number'] = 'Account number is required for digital payments.';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    // ============================================
    // CONVERSION
    // ============================================

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'method_name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'is_active' => $this->isActive,
            'is_digital' => $this->isDigital(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }

    public function toDatabaseArray(): array
    {
        return [
            'method_name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'is_active' => $this->isActive ? 1 : 0
        ];
    }
}