<?php
declare(strict_types=1);

namespace App\Refund\Application\Usecases;

use App\Refund\Domain\Repositories\RefundRepositoryInterface;

class GetRefundRequestsUseCase
{
    private RefundRepositoryInterface $refundRepository;

    public function __construct(RefundRepositoryInterface $refundRepository)
    {
        $this->refundRepository = $refundRepository;
    }

    /**
     * Get all refund requests with filters and pagination
     */
    public function execute(?int $statusId = null, ?int $userId = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $refunds = $this->refundRepository->findAll(
                $statusId,
                $userId,
                $limit,
                $offset
            );

            $total = $this->refundRepository->count(
                $statusId,
                $userId
            );

            return [
                'success' => true,
                'data' => $refunds,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'current_page' => floor($offset / $limit) + 1,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch refund requests: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all refund requests (Admin/Staff only)
     */
    public function getAll(): array
    {
        try {
            $refunds = $this->refundRepository->findAll();
            return [
                'success' => true,
                'data' => $refunds
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch refunds: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all refund requests with pagination
     */
    public function getAllPaginated(int $limit = 20, int $offset = 0): array
    {
        try {
            $refunds = $this->refundRepository->findAll(null, null, $limit, $offset);
            $total = $this->refundRepository->count();
            
            return [
                'success' => true,
                'data' => $refunds,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'current_page' => floor($offset / $limit) + 1,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch refunds: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get pending refund requests (Admin/Staff only)
     */
    public function getPending(): array
    {
        try {
            $refunds = $this->refundRepository->findAllPending();
            return [
                'success' => true,
                'data' => $refunds
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch pending refunds: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get refund requests by user (Customer only - their own refunds)
     */
    public function getByUser(int $userId): array
    {
        try {
            $refunds = $this->refundRepository->findByUser($userId);
            return [
                'success' => true,
                'data' => $refunds
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch your refunds: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get refund statuses
     */
    public function getStatuses(): array
    {
        try {
            $statuses = $this->refundRepository->getRefundStatuses();
            return [
                'success' => true,
                'data' => $statuses
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch refund statuses: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get refund by ID with details
     */
    public function getById(int $refundId): array
    {
        try {
            $refund = $this->refundRepository->findById($refundId);
            if (!$refund) {
                return [
                    'success' => false,
                    'message' => 'Refund request not found'
                ];
            }

            return [
                'success' => true,
                'data' => $refund->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch refund details: ' . $e->getMessage()
            ];
        }
    }
}