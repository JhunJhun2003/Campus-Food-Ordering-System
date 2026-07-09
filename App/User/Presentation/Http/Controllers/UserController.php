<?php
declare(strict_types=1);

namespace App\User\Presentation\Http\Controllers;

use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Application\Usecases\GetProfileUseCase;
use App\User\Application\Usecases\UpdateProfileUseCase;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Password;

/**
 * User Controller
 * Follows SOLID principles with Dependency Injection
 * No 'new' keyword - all dependencies are injected
 */
class UserController
{
    private UserRepositoryInterface $userRepository;

    /**
     * Constructor with Dependency Injection
     * All dependencies are injected, not created inside
     */
    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user
     */
    public function register(): array
    {
        $request = new RegisterUserRequest(
            $_POST['name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['phone'] ?? ''
        );

        $useCase = new RegisterUserUseCase($this->userRepository);
        $response = $useCase->execute($request);

        return [
            'success' => $response->success,
            'message' => $response->message,
            'user' => $response->user,
            'errors' => $response->errors ?? null
        ];
    }

    /**
     * Login user
     */
    public function login(): array
    {
        $request = new LoginUserRequest(
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            isset($_POST['remember'])
        );

        $useCase = new LoginUserUseCase($this->userRepository);
        $response = $useCase->execute($request);

        if ($response->success && $response->user) {
            $_SESSION['user_id'] = $response->user->getId()->getValue();
            $_SESSION['user_name'] = $response->user->getName();
            $_SESSION['user_email'] = $response->user->getEmail()->getValue();
            $_SESSION['user_role'] = $response->user->getRoleName();
            $_SESSION['user_verified'] = $response->user->isVerified();
            
            if ($request->remember) {
                setcookie('user_email', $response->user->getEmail()->getValue(), time() + (7 * 24 * 60 * 60), '/');
            }
        }

        return [
            'success' => $response->success,
            'message' => $response->message,
            'user' => $response->user,
            'redirect' => $response->redirectUrl ?? $this->getRedirectUrl($_SESSION['user_role'] ?? 'user')
        ];
    }

    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl(string $role): string
    {
        $role = strtolower($role);
        return match ($role) {
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            default => '/Campus-Food-Ordering-System/view/customer/dashboard.php'
        };
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie('user_email', '', time() - 3600, '/');
        
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        exit();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id'])
            && !empty($_SESSION['user_name'])
            && !empty($_SESSION['user_role']);
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return !empty($_SESSION['user_verified']) && $_SESSION['user_verified'] === true;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user',
            'is_verified' => $_SESSION['user_verified'] ?? false
        ];
    }

    /**
     * Require authentication
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
    }

    /**
     * Require verified user
     */
    public function requireVerified(): void
    {
        $this->requireAuth();
        if (!$this->isVerified()) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/verify-email.php');
            exit();
        }
    }

    /**
     * Require admin role
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
    }

    /**
     * Require staff role
     */
    public function requireStaff(): void
    {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], ['admin', 'staff'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
    }

    /**
     * Require guest (not logged in)
     */
    public function requireGuest(): void
    {
        if ($this->isLoggedIn()) {
            $role = $_SESSION['user_role'] ?? 'user';
            $redirect = match ($role) {
                'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
                'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
                default => '/Campus-Food-Ordering-System/view/customer/dashboard.php'
            };
            header('Location: ' . $redirect);
            exit();
        }
    }

    // ============================================
    // PROFILE METHODS
    // ============================================

    /**
     * Get user profile
     */
    public function getProfile(int $userId): ?array
    {
        $useCase = new GetProfileUseCase($this->userRepository);
        return $useCase->execute($userId);
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): array
    {
        $useCase = new UpdateProfileUseCase($this->userRepository);
        return $useCase->execute($userId, $data);
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        try {
            $user = $this->userRepository->findById(new UserId($userId));
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Verify current password
            if (!$user->getPassword()->verify($currentPassword)) {
                return ['success' => false, 'message' => 'Incorrect current password.'];
            }

            // Change password
            $user->changePassword(new Password($newPassword));
            $this->userRepository->save($user);

            return ['success' => true, 'message' => 'Password changed successfully!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}