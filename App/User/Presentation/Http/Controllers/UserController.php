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
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use Inc\Database;
use App\User\Application\Usecases\LoginWithGoogleUseCase;
use App\User\Application\DTOs\GoogleLoginRequest;
use App\User\Domain\Services\GoogleAuthServiceInterface;
use App\User\Application\Usecases\SendVerificationUseCase;
use App\User\Application\Usecases\VerifyEmailUseCase;

class UserController extends BaseController
{
    private UserRepositoryInterface $userRepository;
    private RegisterUserUseCase $registerUserUseCase;
    private LoginUserUseCase $loginUserUseCase;
    private GetProfileUseCase $getProfileUseCase;
    private UpdateProfileUseCase $updateProfileUseCase;
    private SendVerificationUseCase $sendVerificationUseCase;
    private VerifyEmailUseCase $verifyEmailUseCase;
    private LoginWithGoogleUseCase $loginWithGoogleUseCase;
    private GoogleAuthServiceInterface $googleAuthService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        RegisterUserUseCase $registerUserUseCase,
        LoginUserUseCase $loginUserUseCase,
        GetProfileUseCase $getProfileUseCase,
        UpdateProfileUseCase $updateProfileUseCase,
        SendVerificationUseCase $sendVerificationUseCase,
        VerifyEmailUseCase $verifyEmailUseCase,
        LoginWithGoogleUseCase $loginWithGoogleUseCase,
        GoogleAuthServiceInterface $googleAuthService
    ) {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->registerUserUseCase = $registerUserUseCase;
        $this->loginUserUseCase = $loginUserUseCase;
        $this->getProfileUseCase = $getProfileUseCase;
        $this->updateProfileUseCase = $updateProfileUseCase;
        $this->sendVerificationUseCase = $sendVerificationUseCase;
        $this->verifyEmailUseCase = $verifyEmailUseCase;
        $this->loginWithGoogleUseCase = $loginWithGoogleUseCase;
        $this->googleAuthService = $googleAuthService;
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
            $_POST['phone'] ?? '',
            $_POST['g-recaptcha-response'] ?? null 
        );

        $response = $this->registerUserUseCase->execute($request);

        return [
            'success' => $response->success,
            'message' => $response->message,
            'user' => $response->user,
            'errors' => $response->errors ?? null,
            
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
            isset($_POST['remember']),
             $_POST['g-recaptcha-response'] ?? null
        );

        $response = $this->loginUserUseCase->execute($request);

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

        $redirectUrl = $response->redirectUrl;
        if ($redirectUrl === null && $response->success && $response->user) {
            $redirectUrl = $this->getRedirectUrl(
                $_SESSION['user_role'] ?? 'user',
                $response->user->getId()->getValue()
            );
        }

        return [
            'success' => $response->success,
            'message' => $response->message,
            'user' => $response->user,
            'redirect' => $redirectUrl
        ];
    }

    private function getRedirectUrl(string $role, int $userId): string
    {
        $role = strtolower($role);

        if ($role === 'admin') {
            return '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php';
        }

        if ($role === 'staff') {
            return '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php';
        }

        if (!in_array($role, ['user', 'customer', 'admin', 'staff'], true)) {
            return '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php';
        }

        $adminPermissions = [
            'view_dashboard',
            'manage_users',
            'manage_menu',
            'manage_orders',
            'manage_settings',
            'view_reports',
        ];

        try {
            $repository = new AccessControlRepository(Database::getConnection());
            $checkPermission = new CheckPermissionUseCase($repository);
            foreach ($adminPermissions as $permission) {
                if ($checkPermission->execute($userId, $permission)) {
                    return '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php';
                }
            }
        } catch (\Throwable $e) {
            // Ignore permission check failures and fall back to default
        }

        return '/Campus-Food-Ordering-System/view/customer/dashboard.php';
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
     * Overrides parent to add session data
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
        // Use parent's requireAuthentication and authorize methods
        $this->requireAuthentication();
        $this->authorizeResource($userId, 'manage_users');
        
        return $this->getProfileUseCase->execute($userId);
    }

    /**
     * Update user profile - Users can update their own, admin can update any
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Use parent's requireAuthentication and authorize methods
        $this->requireAuthentication();
        $this->authorizeResource($userId, 'manage_users');
        
        return $this->updateProfileUseCase->execute($userId, $data);
    }

    /**
     * Change user password - Users can change their own password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        // Use parent's requireAuthentication method
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
    // GOOGLE LOGIN METHODS
    // ============================================

    /**
     * Redirect to Google OAuth
     */
    public function googleLogin(): void
    {
        $authUrl = $this->googleAuthService->getAuthUrl();
        header('Location: ' . $authUrl);
        exit();
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(): void
    {
        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            $_SESSION['error'] = 'Google authentication failed: ' . $error;
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }

        if (!$code) {
            $_SESSION['error'] = 'No authorization code received from Google.';
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
            exit();
        }

        $request = new GoogleLoginRequest($code);
        $response = $this->loginWithGoogleUseCase->execute($request);

        if ($response->success) {
            $_SESSION['success'] = $response->message;
            header('Location: ' . $response->redirectUrl);
        } else {
            $errorMessage = $response->message;
            if (!empty($_SESSION['google_auth_error'])) {
                $errorMessage .= ' Details: ' . $_SESSION['google_auth_error'];
                unset($_SESSION['google_auth_error']);
            }

            $_SESSION['error'] = $errorMessage;
            header('Location: /Campus-Food-Ordering-System/view/entrance/login.php');
        }
        exit();
    }

    // ============================================
    // AUTHENTICATION HELPERS - REMOVED OVERRIDES
    // These methods now use parent's implementation
    // ============================================

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return !empty($_SESSION['user_verified']) && $_SESSION['user_verified'] === true;
    }

    /**
     * Require authentication - uses parent method
     */
    public function requireAuth(): void
    {
        $this->requireAuthentication();
    }

    /**
     * Require verified email
     */
    public function requireVerified(): void
    {
        $this->requireAuthentication();
        if (!$this->isVerified()) {
            header('Location: /Campus-Food-Ordering-System/view/entrance/verify-email.php');
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

    /**
     * Get current user ID from session
     */
    public function getCurrentUserId(): int
    {
        return parent::getCurrentUserId();
    }
}