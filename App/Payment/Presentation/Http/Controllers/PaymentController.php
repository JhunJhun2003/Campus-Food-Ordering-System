<?php
declare(strict_types=1);

namespace App\Payment\Presentation\Http\Controllers;

use App\Payment\Application\DTOs\CreatePaymentMethodRequest;
use App\Payment\Application\DTOs\UpdatePaymentMethodRequest;
use App\Payment\Application\Usecases\CreatePaymentMethodUseCase;
use App\Payment\Application\Usecases\UpdatePaymentMethodUseCase;
use App\Payment\Application\Usecases\DeletePaymentMethodUseCase;
use App\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Presentation\Http\Controllers\BaseController;

class PaymentController extends BaseController
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
        parent::__construct();
        $this->paymentRepository = $paymentRepository;
        $this->createPaymentMethodUseCase = $createPaymentMethodUseCase;
        $this->updatePaymentMethodUseCase = $updatePaymentMethodUseCase;
        $this->deletePaymentMethodUseCase = $deletePaymentMethodUseCase;
    }

    /**
     * Get active payment methods - No permission needed (public)
     */
    public function getActiveMethods(): array
    {
        return $this->paymentRepository->getActivePaymentMethods();
    }

    /**
     * Get all payment methods - Admin only
     */
    public function getAllMethods(): array
    {
        $this->authorize('manage_payment_methods');
        return $this->paymentRepository->getAllPaymentMethods();
    }

    /**
     * Add a new payment method - Admin only
     */
    public function addMethod(string $name, string $accountName, string $accountNumber): array
    {
        $this->authorize('manage_payment_methods');
        
        $request = new CreatePaymentMethodRequest($name, $accountName, $accountNumber);
        return $this->createPaymentMethodUseCase->execute($request);
    }

    /**
     * Update a payment method - Admin only
     */
    public function updateMethod(int $id, array $data): array
    {
        $this->authorize('manage_payment_methods');
        
        $request = new UpdatePaymentMethodRequest(
            $id,
            $data['name'] ?? $data['method_name'] ?? null,
            $data['account_name'] ?? null,
            $data['account_number'] ?? null,
            isset($data['is_active']) ? (bool) $data['is_active'] : null
        );
        return $this->updatePaymentMethodUseCase->execute($request);
    }

    /**
     * Delete a payment method - Admin only
     */
    public function deleteMethod(int $id): array
    {
        $this->authorize('manage_payment_methods');
        return $this->deletePaymentMethodUseCase->execute($id);
    }
}