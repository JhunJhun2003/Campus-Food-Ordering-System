<?php
declare(strict_types=1);

namespace App\Payment\Application\DTOs;

class CreatePaymentMethodRequest
{
    private string $name;
    private ?string $accountName;
    private ?string $accountNumber;
    private bool $isActive;

    public function __construct(
        string $name,
        ?string $accountName = null,
        ?string $accountNumber = null,
        bool $isActive = true
    ) {
        $this->name = $name;
        $this->accountName = $accountName;
        $this->accountNumber = $accountNumber;
        $this->isActive = $isActive;
    }

    public function getName(): string { return $this->name; }
    public function getAccountName(): ?string { return $this->accountName; }
    public function getAccountNumber(): ?string { return $this->accountNumber; }
    public function isActive(): bool { return $this->isActive; }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors['name'] = 'Payment method name is required.';
        }

        // If not Cash on Delivery, account details are required
        if ($this->name !== 'Cash on Delivery') {
            if (empty($this->accountName)) {
                $errors['account_name'] = 'Account name is required for digital payments.';
            }
            if (empty($this->accountNumber)) {
                $errors['account_number'] = 'Account number is required for digital payments.';
            }
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'is_active' => $this->isActive
        ];
    }
}