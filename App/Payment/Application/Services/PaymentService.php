<?php
declare(strict_types=1);

namespace App\Payment\Application\Services;

use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Payment\Domain\Entities\PaymentMethod;
use Inc\Database;

class PaymentService
{
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Get payment method ID by name
     */
    public function getPaymentMethodIdByName(string $methodName): ?int
    {
        $paymentMethod = $this->paymentRepository->findByName($methodName);
        return $paymentMethod ? $paymentMethod->getId() : null;
    }

    /**
     * Get payment method by name
     */
    public function getPaymentMethodByName(string $methodName): ?PaymentMethod
    {
        return $this->paymentRepository->findByName($methodName);
    }

    /**
     * Create payment for order with IDEMPOTENCY
     */
    public function createPaymentForOrder(
        int $orderId,
        string $paymentMethodName,
        float $amount,
        ?string $transactionImage = null,
        ?string $idempotencyKey = null
    ): ?int {
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction
            $db->beginTransaction();
            
            // ✅ Lock the order to prevent duplicate payment processing
            $order = $this->lockOrder($orderId);
            
            if (!$order) {
                throw new \Exception("Order not found.");
            }
            
            // ✅ Check if payment already exists for this order
            $stmt = $db->prepare("SELECT id FROM payments WHERE order_id = :order_id FOR UPDATE");
            $stmt->execute([':order_id' => $orderId]);
            if ($existingPayment = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                // Payment already exists - return existing ID (idempotent)
                $db->commit();
                return (int) $existingPayment['id'];
            }
            
            // ✅ If idempotency key provided, check if already processed
            if ($idempotencyKey) {
                $stmt = $db->prepare("SELECT id FROM payments WHERE idempotency_key = :key FOR UPDATE");
                $stmt->execute([':key' => $idempotencyKey]);
                if ($existing = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    // Duplicate request detected! Return existing payment
                    $db->commit();
                    return (int) $existing['id'];
                }
            }
            
            $paymentMethod = $this->getPaymentMethodByName($paymentMethodName);
            
            if (!$paymentMethod) {
                throw new \Exception("Payment method '{$paymentMethodName}' not found.");
            }

            $isCOD = $paymentMethodName === 'Cash on Delivery';
            $statusId = $isCOD ? 1 : 2; // 1 = pending, 2 = paid
            $transactionNo = $isCOD ? null : 'TXN-' . strtoupper(uniqid());

            // ✅ Generate idempotency key if not provided
            if (!$idempotencyKey) {
                $idempotencyKey = $this->generateIdempotencyKey($orderId);
            }

            // ✅ Insert payment with idempotency key
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, 
                    payment_method_id, 
                    payment_status_id, 
                    amount, 
                    transaction_no, 
                    transaction_image,
                    idempotency_key,
                    payment_date
                ) VALUES (
                    :order_id, 
                    :payment_method_id, 
                    :payment_status_id, 
                    :amount, 
                    :transaction_no, 
                    :transaction_image, 
                    :idempotency_key, 
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':order_id' => $orderId,
                ':payment_method_id' => $paymentMethod->getId(),
                ':payment_status_id' => $statusId,
                ':amount' => $amount,
                ':transaction_no' => $transactionNo,
                ':transaction_image' => $isCOD ? null : $transactionImage,
                ':idempotency_key' => $idempotencyKey
            ]);
            
            $paymentId = (int) $db->lastInsertId();
            
            // ✅ Commit transaction
            $db->commit();
            return $paymentId;
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            error_log("Failed to create payment record for order {$orderId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lock order for payment processing (pessimistic locking)
     */
    private function lockOrder(int $orderId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, status_id, total_amount FROM orders WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $orderId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Generate idempotency key
     */
    private function generateIdempotencyKey(int $orderId): string
    {
        return 'PAY-' . date('Ymd') . '-' . $orderId . '-' . uniqid();
    }

    /**
     * Get payment by order ID
     */
    public function getPaymentByOrderId(int $orderId): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    p.id as payment_id,
                    p.payment_method_id,
                    p.payment_status_id,
                    p.amount as payment_amount,
                    p.transaction_no,
                    p.transaction_image,
                    p.idempotency_key,
                    p.payment_date,
                    pm.method_name as payment_method_name,
                    pm.account_name as payment_account_name,
                    pm.account_number as payment_account_number,
                    ps.status_name as payment_status_name
                FROM payments p
                LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
                LEFT JOIN payment_statuses ps ON p.payment_status_id = ps.id
                WHERE p.order_id = :order_id
            ");
            $stmt->execute([':order_id' => $orderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Failed to get payment for order {$orderId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all active payment methods
     */
    public function getActivePaymentMethods(): array
    {
        return $this->paymentRepository->getActivePaymentMethods();
    }

    /**
     * Get all payment methods
     */
    public function getAllPaymentMethods(): array
    {
        return $this->paymentRepository->getAllPaymentMethods();
    }

    /**
     * Check if payment method is Cash on Delivery
     */
    public function isCOD(string $paymentMethodName): bool
    {
        return $paymentMethodName === 'Cash on Delivery';
    }

    /**
     * Find payment method by ID
     */
    public function findPaymentMethodById(int $id): ?PaymentMethod
    {
        return $this->paymentRepository->findById($id);
    }

    /**
     * Find payment method by name
     */
    public function findPaymentMethodByName(string $name): ?PaymentMethod
    {
        return $this->paymentRepository->findByName($name);
    }
}