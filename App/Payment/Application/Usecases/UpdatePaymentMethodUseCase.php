<?php
declare(strict_types=1);

namespace App\Payment\Application\Usecases;

use App\Payment\Application\DTOs\UpdatePaymentMethodRequest;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;

class UpdatePaymentMethodUseCase
{
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(UpdatePaymentMethodRequest $request): array
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        // Find existing payment method
        $paymentMethod = $this->paymentRepository->findById($request->getId());
        if (!$paymentMethod) {
            return ['success' => false, 'message' => 'Payment method not found.'];
        }

        // Update entity
        if ($request->getName() !== null) {
            $paymentMethod->update(
                $request->getName(),
                $request->getAccountName(),
                $request->getAccountNumber()
            );
        } else {
            // Only update account details if name not changing
            if ($request->getAccountName() !== null) {
                $paymentMethod->setAccountName($request->getAccountName());
            }
            if ($request->getAccountNumber() !== null) {
                $paymentMethod->setAccountNumber($request->getAccountNumber());
            }
        }

        if ($request->isActive() !== null) {
            if ($request->isActive()) {
                $paymentMethod->activate();
            } else {
                $paymentMethod->deactivate();
            }
        }

        // Save
        $updated = $this->paymentRepository->update($paymentMethod);

        return [
            'success' => $updated,
            'message' => $updated ? 'Payment method updated successfully.' : 'Failed to update payment method.'
        ];
    }
}