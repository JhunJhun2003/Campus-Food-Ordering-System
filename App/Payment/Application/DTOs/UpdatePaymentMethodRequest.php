<?php
declare(strict_types=1);

namespace App\Payment\Application\DTOs;

class UpdatePaymentMethodRequest
{
    private int $id;
    private ?string $name;
    private ?string $accountName;
    private ?string $accountNumber;
    private ?bool $isActive;

    public function __construct(
        int $id,
        ?string $name = null,
        ?string $accountName = null,
        ?string $accountNumber = null,
        ?bool $isActive = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->isActive = $isActive;
    }

    public function getId(): int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function getAccountName(): ?string { return $this->accountName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function isActive(): ?bool { return $this->isActive; }

    public function validate(): array
    {
        $errors = [];

        if ($this->id <= 0) {
            $errors['id'] = 'Invalid payment method ID.';
        }

        if ($this->name !== null && empty($this->name)) {
            $errors['name'] = 'Payment method name cannot be empty.';
        }

        return $errors;
    }

    public function toArray(): array
    {
        return array_filter([
            'method_name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'is_active' => $this->isActive
        ], fn($value) => $value !== null);
    }
}