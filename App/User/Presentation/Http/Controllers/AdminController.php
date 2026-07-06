<?php
namespace App\User\Presentation\Http\Controllers;

use App\User\Application\Usecases\GetDashboardStatsUseCase;
use App\User\Application\Usecases\GetReportsUseCase;
use App\User\Application\Usecases\GetSettingsUseCase;
use App\User\Application\Usecases\UpdateSettingsUseCase;
use App\User\Infrastructure\Repositories\UserRepository;

class AdminController
{
    private UserRepository $userRepository;
    private UserController $userController;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->userController = new UserController();
    }

    // Dashboard
    public function dashboard(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetDashboardStatsUseCase($this->userRepository);
        return $useCase->execute();
    }

    // Reports
    public function reports(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetReportsUseCase($this->userRepository);
        return $useCase->execute();
    }

    // ============================================
    // SETTINGS METHODS
    // ============================================

    /**
     * Get all settings
     */
    public function getSettings(): array
    {
        $this->userController->requireAdmin();
        $useCase = new GetSettingsUseCase($this->userRepository);
        return $useCase->execute();
    }

    /**
     * Update settings from POST request
     */
    public function updateSettingsFromRequest(): array
    {
        $this->userController->requireAdmin();
        
        // Filter only setting fields
        $postData = array_filter($_POST, function($key) {
            return strpos($key, 'setting_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        
        // Convert to key-value pairs
        $settingsToUpdate = [];
        foreach ($postData as $key => $value) {
            $cleanKey = str_replace('setting_', '', $key);
            $settingsToUpdate[$cleanKey] = trim($value);
        }
        
        $useCase = new UpdateSettingsUseCase($this->userRepository);
        return $useCase->execute($settingsToUpdate);
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        return $this->userController->getCurrentUser();
    }

    /**
     * Require staff or admin access
     */
    public function requireStaffAccess(): void
    {
        $this->userController->requireAuth();
        if (!in_array($_SESSION['user_role'], ['admin', 'staff'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
    }

    /**
     * Require admin access
     */
    public function requireAdminAccess(): void
    {
        $this->userController->requireAdmin();
    }

    /**
     * Check if current user has staff access
     */
    public function hasStaffAccess(): bool
    {
        if (!$this->userController->isLoggedIn()) {
            return false;
        }
        return in_array($_SESSION['user_role'], ['admin', 'staff']);
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        if (!$this->userController->isLoggedIn()) {
            return false;
        }
        return $_SESSION['user_role'] === 'admin';
    }

    /**
     * Create user by admin (auto-verified)
     */
    public function createUser(array $data): array
    {
        $this->userController->requireAdmin();
        
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'message' => 'Name, email, and password are required.'
                ];
            }

            // Check if email already exists
            if ($this->userRepository->emailExists($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'Email already exists.'
                ];
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Create user with is_verified = 1 (auto-verified by admin)
            $sql = "INSERT INTO users (role_id, name, email, password, phone, address, is_verified, email_verified_at, created_at, updated_at) 
                    VALUES (:role_id, :name, :email, :password, :phone, :address, 1, NOW(), NOW(), NOW())";
            
            $db = $this->userRepository->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':role_id' => $data['role_id'] ?? 3, // Default to 'user' role
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':password' => $hashedPassword,
                ':phone' => $data['phone'] ?? null,
                ':address' => $data['address'] ?? null
            ]);

            $userId = (int) $db->lastInsertId();

            return [
                'success' => true,
                'message' => 'User created successfully and is verified.',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update user (admin)
     */
    public function updateUser(int $userId, array $data): array
    {
        $this->userController->requireAdmin();
        
        try {
            $result = $this->userRepository->updateUser($userId, $data);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update user.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(int $userId): array
    {
        $this->userController->requireAdmin();
        
        try {
            if ($userId === 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete the master admin account.'
                ];
            }

            $result = $this->userRepository->deleteUser($userId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete user.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all users (with verification status)
     */
    public function getAllUsersWithVerification(): array
    {
        $this->userController->requireAdmin();
        
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
            
            return [
                'success' => true,
                'users' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to fetch users: ' . $e->getMessage(),
                'users' => []
            ];
        }
    }
}