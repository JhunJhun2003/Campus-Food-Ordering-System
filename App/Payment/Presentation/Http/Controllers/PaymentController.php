<?php
declare(strict_types=1);

namespace App\Payment\Presentation\Http\Controllers;

use App\Payment\Application\DTOs\CreatePaymentMethodRequest;
use App\Payment\Application\DTOs\UpdatePaymentMethodRequest;
use App\Payment\Application\Usecases\CreatePaymentMethodUseCase;
use App\Payment\Application\Usecases\UpdatePaymentMethodUseCase;
use App\Payment\Application\Usecases\DeletePaymentMethodUseCase;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;

/**
 * Payment Controller
 * Follows SOLID principles with Dependency Injection
 * No 'new' keyword - all dependencies are injected
 */
class PaymentController
{
    private PaymentRepositoryInterface $paymentRepository;
    private CreatePaymentMethodUseCase $createPaymentMethodUseCase;
    private UpdatePaymentMethodUseCase $updatePaymentMethodUseCase;
    private DeletePaymentMethodUseCase $deletePaymentMethodUseCase;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        CreatePaymentMethodUseCase $createPaymentMethodUseCase,
        UpdatePaymentMethodUseCase $updatePaymentMethodUseCase,
        DeletePaymentMethodUseCase $deletePaymentMethodUseCase
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->createPaymentMethodUseCase = $createPaymentMethodUseCase;
        $this->updatePaymentMethodUseCase = $updatePaymentMethodUseCase;
        $this->deletePaymentMethodUseCase = $deletePaymentMethodUseCase;
    }

    /**
     * Get active payment methods
     */
    public function getActiveMethods(): array
    {
        return $this->paymentRepository->getActivePaymentMethods();
    }

    /**
     * Get all payment methods
     */
    public function getAllMethods(): array
    {
        return $this->paymentRepository->getAllPaymentMethods();
    }

    /**
     * Add a new payment method
     */
    public function addMethod(string $name, string $accountName, string $accountNumber): array
    {
        $request = new CreatePaymentMethodRequest($name, $accountName, $accountNumber);
        return $this->createPaymentMethodUseCase->execute($request);
    }

    /**
     * Update a payment method
     */
    public function updateMethod(int $id, array $data): array
    {
        $request = new UpdatePaymentMethodRequest(
            $id,
            $data['name'] ?? null,
            $data['account_name'] ?? null,
            $data['account_number'] ?? null,
            $data['is_active'] ?? null
        );
        return $this->updatePaymentMethodUseCase->execute($request);
    }

    /**
     * Delete a payment method
     */
    public function deleteMethod(int $id): array
    {
        return $this->deletePaymentMethodUseCase->execute($id);
    }
}