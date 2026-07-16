<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Application\Usecases\GetDashboardStatsUseCase;
use App\User\Application\Usecases\GetReportsUseCase;
use App\User\Application\Usecases\GetSettingsUseCase;
use App\User\Application\Usecases\UpdateSettingsUseCase;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\Order\Domain\Repositories\OrderRepositoryInterface;
use App\Food\Domain\Repositories\FoodRepositoryInterface;
use App\Shared\Presentation\Http\Controllers\BaseController;

class AdminController extends BaseController
{
    private UserRepositoryInterface $userRepository;
    private OrderRepositoryInterface $orderRepository;
    private FoodRepositoryInterface $foodRepository;
    private UserController $userController;
    private GetSettingsUseCase $getSettingsUseCase;
    private UpdateSettingsUseCase $updateSettingsUseCase;

    public function __construct(
        UserRepositoryInterface $userRepository,
        OrderRepositoryInterface $orderRepository,
        FoodRepositoryInterface $foodRepository,
        UserController $userController
    ) {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->orderRepository = $orderRepository;
        $this->foodRepository = $foodRepository;
        $this->userController = $userController;
        
        // ✅ Initialize settings use cases
        $this->getSettingsUseCase = new GetSettingsUseCase($userRepository);
        $this->updateSettingsUseCase = new UpdateSettingsUseCase($userRepository);
    }

    /**
     * Dashboard statistics - Admin only
     */
    public function dashboard(): array
    {
        $this->authorizeAny([
            'view_dashboard',
            'manage_users',
            'manage_menu',
            'manage_orders',
            'manage_settings',
            'view_reports',
        ]);
        
        return [
            'total_users' => $this->userRepository->getTotalUsers(),
            'total_foods' => $this->foodRepository->count(),
            'total_orders' => $this->orderRepository->getTotalOrders(),
            'pending_orders' => $this->orderRepository->getPendingOrders(),
            'recent_orders' => $this->orderRepository->getRecentOrders(5),
        ];
    }

    /**
     * Reports - Admin only
     */
    public function reports(): array
    {
        $this->authorizeAny([
            'view_reports',
            'manage_orders',
            'manage_settings',
            'view_dashboard',
        ]);
        
        return [
            'total_revenue' => $this->orderRepository->getTotalRevenue(),
            'total_orders' => $this->orderRepository->getTotalOrders(),
            'completed_orders' => $this->orderRepository->getCompletedOrders(),
            'pending_orders' => $this->orderRepository->getPendingOrders(),
            'monthly_revenue' => $this->orderRepository->getMonthlyRevenue(6),
            'order_stats' => $this->orderRepository->getOrderStats(),
        ];
    }

    // ============================================
    // SETTINGS METHODS - Admin only
    // ============================================

    /**
     * Get all settings
     */
    public function getSettings(): array
    {
        $this->authorize('manage_settings');
        return $this->userRepository->getAllSettings();
    }

    /**
     * Update settings from request data
     */
    public function updateSettingsFromRequest(): array
    {
        $this->authorize('manage_settings');
        
        $postData = array_filter($_POST, function($key) {
            return strpos($key, 'setting_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        $settingsToUpdate = [];
        foreach ($postData as $key => $value) {
            $cleanKey = str_replace('setting_', '', $key);
            $settingsToUpdate[$cleanKey] = trim($value);
        }
        
        $result = $this->userRepository->updateSettings($settingsToUpdate);
        
        // ✅ If maintenance mode was changed, log it
        if (isset($settingsToUpdate['maintenance_mode'])) {
            $status = $settingsToUpdate['maintenance_mode'] == '1' ? 'ON' : 'OFF';
            $_SESSION['success'] = "Maintenance mode turned {$status}";
        }
        
        return $result;
    }

    // ============================================
    // USER MANAGEMENT - Admin only
    // ============================================

    public function getCurrentUser(): ?array
    {
        return $this->userController->getCurrentUser();
    }

    public function requireStaffAccess(): void
    {
        $this->requireStaff();
    }

    public function requireAdminAccess(): void
    {
        $this->requireAdmin();
    }

    public function hasStaffAccess(): bool
    {
        return $this->isStaff() || $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return parent::isAdmin();
    }

    public function createUser(array $data): array
    {
        $this->authorize('manage_users');
        
        try {
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'Name, email, and password are required.'];
            }

            if ($this->userRepository->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Email already exists.'];
            }

            $userId = $this->userRepository->createUser(
                $data['name'],
                $data['email'],
                $data['password'],
                $data['phone'] ?? '',
                $data['role_id'] ?? 3,
                true
            );

            return [
                'success' => true,
                'message' => 'User created successfully and is verified.',
                'user_id' => $userId
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()];
        }
    }

    public function updateUser(int $userId, array $data): array
    {
        $this->authorize('manage_users');
        
        try {
            $result = $this->userRepository->updateUser($userId, $data);
            return [
                'success' => $result,
                'message' => $result ? 'User updated successfully.' : 'Failed to update user.'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteUser(int $userId): array
    {
        $this->authorize('manage_users');
        
        try {
            if ($userId === 1) {
                return ['success' => false, 'message' => 'Cannot delete the master admin account.'];
            }

            $result = $this->userRepository->deleteUser($userId);
            return [
                'success' => $result,
                'message' => $result ? 'User deleted successfully.' : 'Failed to delete user.'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getAllUsersWithVerification(): array
    {
        $this->authorize('manage_users');
        
        try {
            $users = $this->userRepository->findAll();
            $result = [];
            foreach ($users as $user) {
                $userArray = $user->toArray();
                $userArray['is_verified'] = $user->isVerified();
                $userArray['email_verified_at'] = $user->getEmailVerifiedAt() ? 
                    $user->getEmailVerifiedAt()->format('Y-m-d H:i:s') : null;
                $result[] = $userArray;
            }
            return ['success' => true, 'users' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage(), 'users' => []];
        }
    }
}