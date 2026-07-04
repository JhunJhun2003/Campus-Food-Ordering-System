<?php
namespace App\User\Presentation\Http\Controllers;

use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Infrastructure\Repositories\UserRepository;

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

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
        return match ($role) {
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            default => '/Campus-Food-Ordering-System/view/customer/dashboard.php'
        };
    }

    public function logout(): void
    {
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Clear remember me cookie
        setcookie('user_email', '', time() - 3600, '/');
        
        // Redirect to login page with full path
        header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        exit();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }

    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /Campus-Food-Ordering-System/view/customer/menu.php');
            exit();
        }
    }

    public function requireStaff(): void
    {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], ['admin', 'staff'])) {
            header('Location: /Campus-Food-Ordering-System/view/customer/menu.php');
            exit();
        }
    }

    public function requireGuest(): void
    {
        if ($this->isLoggedIn()) {
            $role = $_SESSION['user_role'];
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
 * Get user profile
 */
public function getProfile(int $userId): ?array
{
    $useCase = new \App\User\Application\Usecases\GetProfileUseCase(
        new \App\User\Infrastructure\Repositories\UserRepository()
    );
    return $useCase->execute($userId);
}

/**
 * Update user profile
 */
public function updateProfile(int $userId, array $data): array
{
    $useCase = new \App\User\Application\Usecases\UpdateProfileUseCase(
        new \App\User\Infrastructure\Repositories\UserRepository()
    );
    return $useCase->execute($userId, $data);
}
}