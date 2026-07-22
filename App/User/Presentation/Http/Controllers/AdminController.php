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
public function reports(array $filters = []): array
{
    $this->authorizeAny([
        'view_reports',
        'manage_orders',
        'manage_settings',
        'view_dashboard',
    ]);

    // Get filters from the passed array or fallback to $_GET
    $start = $filters['start'] ?? $_GET['start'] ?? null;
    $end = $filters['end'] ?? $_GET['end'] ?? null;
    $period = $filters['period'] ?? $_GET['period'] ?? 'last30';
    $group = $filters['group'] ?? $_GET['group'] ?? 'day';
    $month = $filters['month'] ?? $_GET['month'] ?? null;
    $year = $filters['year'] ?? $_GET['year'] ?? null;

    // Calculate date range if not provided
    if (!$start || !$end) {
        switch ($period) {
            case 'this_month':
                $start = (new \DateTime('first day of this month'))->format('Y-m-d');
                $end = (new \DateTime('last day of this month'))->format('Y-m-d');
                break;
            case 'this_year':
                $start = (new \DateTime('first day of January ' . date('Y')))->format('Y-m-d');
                $end = (new \DateTime('last day of December ' . date('Y')))->format('Y-m-d');
                break;
            case 'month_year':
                if ($month && $year) {
                    $start = (new \DateTime(sprintf('%s-%s-01', $year, $month)))->format('Y-m-d');
                    $end = (new \DateTime(sprintf('%s-%s-t', $year, $month)))->format('Y-m-d');
                }
                break;
            case 'custom':
                if ($start && $end) {
                    // already set
                }
                break;
            case 'last30':
            default:
                $end = (new \DateTime())->format('Y-m-d');
                $start = (new \DateTime())->modify('-29 days')->format('Y-m-d');
                break;
        }
    }

    // If we have a date range, get filtered data
    if ($start && $end) {
        // Get filtered data
        $totalRevenue = $this->orderRepository->getRevenueBetween($start, $end);
        $totalOrders = $this->orderRepository->getOrdersBetween($start, $end);
        $completedOrders = $this->orderRepository->getCompletedOrdersBetween($start, $end);
        $pendingOrders = $this->orderRepository->getPendingOrdersBetween($start, $end);
        
        $daily = $this->orderRepository->getDailyRevenueBetween($start, $end);
        
        // Group the data based on the group parameter
        $chartData = $this->groupChartData($daily, $group);
        
    } else {
        // No date range - use all data
        $totalRevenue = $this->orderRepository->getTotalRevenue();
        $totalOrders = $this->orderRepository->getTotalOrders();
        $completedOrders = $this->orderRepository->getCompletedOrders();
        $pendingOrders = $this->orderRepository->getPendingOrders();
        $chartData = $this->orderRepository->getMonthlyRevenue(6);
    }

    return [
        'total_revenue' => $totalRevenue,
        'total_orders' => $totalOrders,
        'completed_orders' => $completedOrders,
        'pending_orders' => $pendingOrders,
        'monthly_revenue' => $chartData,
        'order_stats' => $this->orderRepository->getOrderStats(),
    ];
}

/**
 * Group chart data based on the group parameter
 */
private function groupChartData(array $daily, string $group): array
{
    if (empty($daily)) {
        return [];
    }

    switch ($group) {
        case 'month':
            $agg = [];
            foreach ($daily as $d) {
                $key = substr($d['date'], 0, 7); // YYYY-MM
                if (!isset($agg[$key])) {
                    $agg[$key] = [
                        'month' => date('M Y', strtotime($key . '-01')),
                        'revenue' => 0.0,
                        'orders' => 0,
                    ];
                }
                $agg[$key]['revenue'] += $d['revenue'];
                $agg[$key]['orders'] += $d['orders'];
            }
            return array_values($agg);

        case 'year':
            // Group by month within the year
            $agg = [];
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Determine which year to show
            $year = date('Y');
            // Try to get year from the data
            if (!empty($daily)) {
                $firstDate = $daily[0]['date'] ?? '';
                if ($firstDate) {
                    $year = date('Y', strtotime($firstDate));
                }
            }
            
            // Initialize all months with 0
            for ($m = 1; $m <= 12; $m++) {
                $monthKey = sprintf('%04d-%02d', $year, $m);
                $agg[$monthKey] = [
                    'month' => $monthNames[$m - 1],
                    'revenue' => 0.0,
                    'orders' => 0,
                ];
            }
            
            // Fill with actual data
            foreach ($daily as $d) {
                $monthKey = substr($d['date'], 0, 7); // YYYY-MM
                if (isset($agg[$monthKey])) {
                    $agg[$monthKey]['revenue'] += $d['revenue'];
                    $agg[$monthKey]['orders'] += $d['orders'];
                }
            }
            
            return array_values($agg);

        case 'week':
            $agg = [];
            foreach ($daily as $d) {
                $timestamp = strtotime($d['date']);
                $weekKey = date('o-W', $timestamp);
                if (!isset($agg[$weekKey])) {
                    $agg[$weekKey] = [
                        'month' => 'W' . date('W', $timestamp) . ' ' . date('o', $timestamp),
                        'revenue' => 0.0,
                        'orders' => 0,
                    ];
                }
                $agg[$weekKey]['revenue'] += $d['revenue'];
                $agg[$weekKey]['orders'] += $d['orders'];
            }
            return array_values($agg);

        case 'day':
        default:
            return array_map(function ($d) {
                return [
                    'month' => date('d M', strtotime($d['date'])),
                    'revenue' => $d['revenue'],
                    'orders' => $d['orders'],
                ];
            }, $daily);
    }
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