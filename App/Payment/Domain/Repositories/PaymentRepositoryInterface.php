<?php
namespace App\Payment\Domain\Repositories;

interface PaymentRepositoryInterface
{
    public function getActivePaymentMethods(): array;
    public function getAllPaymentMethods(): array;
    public function addPaymentMethod(string $name, string $accountName, string $accountNumber): int;
    public function updatePaymentMethod(int $id, array $data): bool;
    public function deletePaymentMethod(int $id): bool;
}