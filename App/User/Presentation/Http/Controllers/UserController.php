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
use App\Shared\Presentation\Http\Controllers\BaseController;

class UserController extends BaseController  // ✅ Extend BaseController
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $userRepository
    ) {
        parent::__construct();  // ✅ Call parent constructor
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user - No auth needed (public)
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
     * Login user - No auth needed (public)
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
            $_SESSION['role_id'] = $response->user->getRoleId();
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
     * Logout user - No auth needed
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
     * Get current user data - Authenticated users only
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
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
     * Get user profile - Users can view their own, admin can view any
     */
    public function getProfile(int $userId): ?array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId, 'manage_users');
        
        $useCase = new GetProfileUseCase($this->userRepository);
        return $useCase->execute($userId);
    }

    /**
     * Update user profile - Users can update their own, admin can update any
     */
    public function updateProfile(int $userId, array $data): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId, 'manage_users');
        
        $useCase = new UpdateProfileUseCase($this->userRepository);
        return $useCase->execute($userId, $data);
    }

    /**
     * Change user password - Users can change their own password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $this->requireAuthentication();
        $this->authorizeResource($userId);
        
        try {
            $user = $this->userRepository->findById(new UserId($userId));
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            if (!$user->getPassword()->verify($currentPassword)) {
                return ['success' => false, 'message' => 'Incorrect current password.'];
            }

            $user->changePassword(new Password($newPassword));
            $this->userRepository->save($user);

            return ['success' => true, 'message' => 'Password changed successfully!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ============================================
    // AUTHENTICATION HELPERS (Using BaseController methods)
    // ============================================

    public function isLoggedIn(): bool
    {
        return $this->isAuthenticated();
    }

    public function isVerified(): bool
    {
        return !empty($_SESSION['user_verified']) && $_SESSION['user_verified'] === true;
    }

    public function requireAuth(): void
    {
        $this->requireAuthentication();
    }

    public function requireVerified(): void
    {
        $this->requireAuth();
        if (!$this->isVerified()) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/verify-email.php');
            exit();
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        $role = $_SESSION['user_role'] ?? '';
        if ($role !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
    }

    public function requireStaff(): void
    {
        $this->requireAuth();
        $role = $_SESSION['user_role'] ?? '';
        if (!in_array($role, ['admin', 'staff'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
            exit();
        }
    }

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

    /**
     * Get current user ID from session
     */
    public function getCurrentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }
}