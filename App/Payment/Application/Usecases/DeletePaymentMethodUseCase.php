<?php
declare(strict_types=1);

namespace App\Payment\Application\Usecases;

use App\Payment\Domain\Repositories\PaymentRepositoryInterface;

class DeletePaymentMethodUseCase
{
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(int $id): array
    {
        // Check if exists
        $paymentMethod = $this->paymentRepository->findById($id);
        if (!$paymentMethod) {
            return ['success' => false, 'message' => 'Payment method not found.'];
        }

        // Don't allow deleting Cash on Delivery
        if ($paymentMethod->getName() === 'Cash on Delivery') {
            return ['success' => false, 'message' => 'Cash on Delivery cannot be deleted.'];
        }

        if ($this->paymentRepository->countPaymentsByMethodId($id) > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete this payment method because it has existing payment records. Deactivate it instead.',
            ];
        }

        try {
            $deleted = $this->paymentRepository->delete($id);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'message' => 'Cannot delete this payment method because it is linked to existing payments. Deactivate it instead.',
                ];
            }

            throw $e;
        }

        return [
            'success' => $deleted,
            'message' => $deleted ? 'Payment method deleted successfully.' : 'Failed to delete payment method.'
        ];
    }
}