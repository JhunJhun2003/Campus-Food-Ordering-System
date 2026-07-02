<?php
namespace App\Payment\Domain\Entities;

class PaymentMethod
{
    private ?int $id;
    private string $name;
    private ?string $accountName;
    private ?string $accountNumber;
    private bool $isActive;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;

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
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getAccountName(): ?string { return $this->accountName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }

    // Business methods
    public function isDigital(): bool
    {
        return $this->name !== 'Cash on Delivery';
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTime();
    }

    public function update(string $name, ?string $accountName, ?string $accountNumber): void
    {
        $this->name = $name;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->updatedAt = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}