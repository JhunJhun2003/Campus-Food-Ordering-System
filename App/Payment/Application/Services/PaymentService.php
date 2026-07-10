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
     * Create payment for order
     */
    public function createPaymentForOrder(
        int $orderId,
        string $paymentMethodName,
        float $amount,
        ?string $transactionImage = null
    ): ?int {
        $paymentMethod = $this->getPaymentMethodByName($paymentMethodName);
        
        if (!$paymentMethod) {
            return null;
        }

        $isCOD = $paymentMethodName === 'Cash on Delivery';
        $statusId = $isCOD ? 1 : 2; // 1 = pending, 2 = paid
        $transactionNo = $isCOD ? null : 'TXN-' . strtoupper(uniqid());

        try {
            $db = Database::getConnection();
            
            // Check if payment already exists
            $stmt = $db->prepare("SELECT id FROM payments WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $orderId]);
            if ($stmt->fetch()) {
                return null; // Payment already exists
            }
            
            $stmt = $db->prepare("
                INSERT INTO payments (
                    order_id, 
                    payment_method_id, 
                    payment_status_id, 
                    amount, 
                    transaction_no, 
                    transaction_image,
                    payment_date
                ) VALUES (
                    :order_id, 
                    :payment_method_id, 
                    :payment_status_id, 
                    :amount, 
                    :transaction_no, 
                    :transaction_image,
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':order_id' => $orderId,
                ':payment_method_id' => $paymentMethod->getId(),
                ':payment_status_id' => $statusId,
                ':amount' => $amount,
                ':transaction_no' => $transactionNo,
                ':transaction_image' => $isCOD ? null : $transactionImage
            ]);
            
            return (int) $db->lastInsertId();
            
        } catch (\Exception $e) {
            error_log("Failed to create payment record for order {$orderId}: " . $e->getMessage());
            return null;
        }
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