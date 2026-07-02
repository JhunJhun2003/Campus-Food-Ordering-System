<?php
namespace App\Payment\Presentation\Http\Controllers;

use App\Payment\Infrastructure\Repositories\PaymentRepository;

class PaymentController
{
    private PaymentRepository $paymentRepository;

    public function __construct()
    {
        $this->paymentRepository = new PaymentRepository();
    }

    public function getActiveMethods(): array
    {
        return $this->paymentRepository->getActivePaymentMethods();
    }

    public function getAllMethods(): array
    {
        return $this->paymentRepository->getAllPaymentMethods();
    }

    public function addMethod(string $name, string $accountName, string $accountNumber): array
    {
        try {
            $id = $this->paymentRepository->addPaymentMethod($name, $accountName, $accountNumber);
            return ['success' => true, 'id' => $id, 'message' => 'Payment method added successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateMethod(int $id, array $data): array
    {
        try {
            $updated = $this->paymentRepository->updatePaymentMethod($id, $data);
            return ['success' => $updated, 'message' => $updated ? 'Payment method updated successfully' : 'Failed to update'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteMethod(int $id): array
    {
        try {
            $deleted = $this->paymentRepository->deletePaymentMethod($id);
            return ['success' => $deleted, 'message' => $deleted ? 'Payment method deleted successfully' : 'Failed to delete'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}