<?php
declare(strict_types=1);

namespace App\Refund\Presentation\Http\Controllers;

use App\Refund\Application\Usecases\RequestRefundUseCase;
use App\Refund\Application\Usecases\ApproveRefundUseCase;
use App\Refund\Application\Usecases\RejectRefundUseCase;
use App\Refund\Application\Usecases\GetRefundRequestsUseCase;
use App\Refund\Application\DTOs\RequestRefundRequest;
use App\Refund\Application\DTOs\ApproveRefundRequest;
use App\Refund\Application\DTOs\RejectRefundRequest;
use App\Shared\Presentation\Http\Controllers\BaseController;

class RefundController extends BaseController
{
    private RequestRefundUseCase $requestRefundUseCase;
    private ApproveRefundUseCase $approveRefundUseCase;
    private RejectRefundUseCase $rejectRefundUseCase;
    private GetRefundRequestsUseCase $getRefundRequestsUseCase;

    public function __construct(
        RequestRefundUseCase $requestRefundUseCase,
        ApproveRefundUseCase $approveRefundUseCase,
        RejectRefundUseCase $rejectRefundUseCase,
        GetRefundRequestsUseCase $getRefundRequestsUseCase
    ) {
        parent::__construct();
        $this->requestRefundUseCase = $requestRefundUseCase;
        $this->approveRefundUseCase = $approveRefundUseCase;
        $this->rejectRefundUseCase = $rejectRefundUseCase;
        $this->getRefundRequestsUseCase = $getRefundRequestsUseCase;
    }

    /**
     * ✅ Customer: Request Refund (Only customers can request)
     */
    public function requestRefund(): array
    {
        try {
            // Check if user is logged in
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
                return [
                    'success' => false, 
                    'message' => 'Please login to request a refund'
                ];
            }

            // ✅ Only customers can request refunds
            $userRole = $_SESSION['user_role'] ?? '';
            if (!in_array($userRole, ['customer', 'user'])) {
                return [
                    'success' => false, 
                    'message' => 'Only customers can request refunds'
                ];
            }

            $userId = (int) $_SESSION['user_id'];

            // Get POST data
            $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            
            // Validate
            if ($orderId <= 0) {
                return ['success' => false, 'message' => 'Invalid order ID'];
            }
            
            if (empty($reason) || strlen($reason) < 5) {
                return ['success' => false, 'message' => 'Please provide a reason (minimum 5 characters)'];
            }
            
            if (strlen($reason) > 500) {
                return ['success' => false, 'message' => 'Reason is too long (maximum 500 characters)'];
            }
            
            $request = new RequestRefundRequest(
                $orderId,
                $userId,
                $reason
            );
            
            return $this->requestRefundUseCase->execute($request);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to request refund: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Admin: Approve Refund (Only admin can approve)
     */
    public function approveRefund(): array
    {
        try {
            // Check if user is logged in
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
                return [
                    'success' => false, 
                    'message' => 'Please login to approve refund'
                ];
            }

            // ✅ Only admin and staff can approve refunds
            $userRole = $_SESSION['user_role'] ?? '';
            if (!in_array($userRole, ['admin', 'staff'])) {
                return [
                    'success' => false, 
                    'message' => 'Only admin or staff can approve refunds'
                ];
            }

            $adminId = (int) $_SESSION['user_id'];

            $refundId = isset($_POST['refund_id']) ? (int) $_POST['refund_id'] : 0;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            if ($refundId <= 0) {
                return ['success' => false, 'message' => 'Invalid refund ID'];
            }
            
            $request = new ApproveRefundRequest(
                $refundId,
                $adminId,
                $notes ?: null
            );
            
            return $this->approveRefundUseCase->execute($request);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to approve refund: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Admin: Reject Refund (Only admin can reject)
     */
    public function rejectRefund(): array
    {
        try {
            // Check if user is logged in
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
                return [
                    'success' => false, 
                    'message' => 'Please login to reject refund'
                ];
            }

            // ✅ Only admin and staff can reject refunds
            $userRole = $_SESSION['user_role'] ?? '';
            if (!in_array($userRole, ['admin', 'staff'])) {
                return [
                    'success' => false, 
                    'message' => 'Only admin or staff can reject refunds'
                ];
            }

            $adminId = (int) $_SESSION['user_id'];

            $refundId = isset($_POST['refund_id']) ? (int) $_POST['refund_id'] : 0;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            if ($refundId <= 0) {
                return ['success' => false, 'message' => 'Invalid refund ID'];
            }
            
            $request = new RejectRefundRequest(
                $refundId,
                $adminId,
                $notes ?: null
            );
            
            return $this->rejectRefundUseCase->execute($request);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reject refund: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Admin: Get all refund requests (Admin only)
     */
    public function getRefundRequests(): array
    {
        try {
            // ✅ Only admin and staff can view all refunds
            $userRole = $_SESSION['user_role'] ?? '';
            if (!in_array($userRole, ['admin', 'staff'])) {
                return [
                    'success' => false, 
                    'message' => 'Only admin or staff can view refund requests'
                ];
            }

            return $this->getRefundRequestsUseCase->getAll();
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get refund requests: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Admin: Get pending refund requests (Admin only)
     */
    public function getPendingRefunds(): array
    {
        try {
            // ✅ Only admin and staff can view pending refunds
            $userRole = $_SESSION['user_role'] ?? '';
            if (!in_array($userRole, ['admin', 'staff'])) {
                return [
                    'success' => false, 
                    'message' => 'Only admin or staff can view pending refunds'
                ];
            }

            return $this->getRefundRequestsUseCase->getPending();
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get pending refunds: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Customer: Get my refund requests (Customer only)
     */
    public function getMyRefundRequests(): array
    {
        try {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
                return [
                    'success' => false, 
                    'message' => 'Please login to view your refunds'
                ];
            }

            $userId = (int) $_SESSION['user_id'];
            return $this->getRefundRequestsUseCase->getByUser($userId);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get your refund requests: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ Check if order can request refund (Helper for views)
     */
    public function canRequestRefund(int $statusId): bool
    {
        // Only pending (1) or confirmed (2) orders can request refund
        return in_array($statusId, [1, 2]);
    }
}