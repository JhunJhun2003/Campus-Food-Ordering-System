<?php
namespace App\User\Presentation\Http\Controllers;

use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\Usecases\RegisterUserUseCase;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\Usecases\LoginUserUseCase;
use App\User\Infrastructure\repositories\UserRepository;

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
            'errors' => $response->errors
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
            $_SESSION['user_role'] = $response->user->getRole();
            
            // Remember me (7 days)
            if ($request->remember) {
                setcookie('user_email', $response->user->getEmail()->getValue(), time() + (7 * 24 * 60 * 60), '/');
            }
        }

        return [
            'success' => $response->success,
            'message' => $response->message,
            'user' => $response->user,
            'redirect' => $response->redirectUrl
        ];
    }

    public function logout(): void
    {
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Clear remember me cookie
        setcookie('user_email', '', time() - 3600, '/');
        
        header('Location: /view/entrance/login.php');
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
            header('Location: /view/entrance/login.php');
            exit();
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: /view/customer/menu.php');
            exit();
        }
    }

    public function requireGuest(): void
    {
        if ($this->isLoggedIn()) {
            if ($_SESSION['user_role'] === 'admin') {
                header('Location: /view/admin/admin-dashboard.php');
            } else {
                header('Location: /view/customer/menu.php');
            }
            exit();
        }
    }
}