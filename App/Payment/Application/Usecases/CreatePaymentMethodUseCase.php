<?php
declare(strict_types=1);

namespace App\Payment\Application\Usecases;

use App\Payment\Application\DTOs\CreatePaymentMethodRequest;
use App\Payment\Domain\Entities\PaymentMethod;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;

class CreatePaymentMethodUseCase
{
    private PaymentRepositoryInterface $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function execute(CreatePaymentMethodRequest $request): array
    {
        // Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        // Check if payment method already exists
        $existing = $this->paymentRepository->findByName($request->getName());
        if ($existing) {
            return ['success' => false, 'message' => 'Payment method already exists.'];
        }

        // Create payment method entity
        $paymentMethod = new PaymentMethod(
            null,
            $request->getName(),
            $request->getAccountName(),
            $request->getAccountNumber(),
            $request->isActive()
        );

        // Save
        $id = $this->paymentRepository->save($paymentMethod);

        return [
            'success' => true,
            'id' => $id,
            'message' => 'Payment method added successfully.'
        ];
    }
}