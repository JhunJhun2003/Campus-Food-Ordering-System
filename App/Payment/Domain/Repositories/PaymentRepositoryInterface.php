<?php
declare(strict_types=1);

namespace App\Payment\Domain\Repositories;

use App\Payment\Domain\Entities\PaymentMethod;

interface PaymentRepositoryInterface
{
    // ============================================
    // READ OPERATIONS
    // ============================================

    /**
     * Get active payment methods
     */
    public function getActivePaymentMethods(): array;

    /**
     * Get all payment methods
     */
    public function getAllPaymentMethods(): array;

    /**
     * Find payment method by ID
     */
    public function findById(int $id): ?PaymentMethod;

    /**
     * Find payment method by name
     */
    public function findByName(string $name): ?PaymentMethod;

    // ============================================
    // WRITE OPERATIONS
    // ============================================

    /**
     * Save a new payment method
     */
    public function save(PaymentMethod $paymentMethod): int;

    /**
     * Update an existing payment method
     */
    public function update(PaymentMethod $paymentMethod): bool;

    /**
     * Delete a payment method
     */
    public function delete(int $id): bool;

    /**
     * Count payment records using a payment method
     */
    public function countPaymentsByMethodId(int $id): int;

    // ============================================
    // PAYMENT RECORD OPERATIONS (For Refund Module)
    // ============================================

    /**
     * Find payment record by order ID
     */
    public function findByOrderId(int $orderId): ?array;

    /**
     * Update payment status
     */
    public function updateStatus(int $paymentId, int $statusId): bool;

    /**
     * Lock payment for update (pessimistic locking)
     */
    public function lockPayment(int $paymentId): ?array;

    /**
     * Create payment record
     */
    public function create(array $data): int;

    /**
     * Find payment by transaction number
     */
    public function findByTransactionNo(string $transactionNo): ?array;
}