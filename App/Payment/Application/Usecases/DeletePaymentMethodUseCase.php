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

        // Delete
        $deleted = $this->paymentRepository->delete($id);

        return [
            'success' => $deleted,
            'message' => $deleted ? 'Payment method deleted successfully.' : 'Failed to delete payment method.'
        ];
    }
}