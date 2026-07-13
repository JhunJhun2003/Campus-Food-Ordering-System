<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\Services\GoogleAuthServiceInterface;
use App\User\Application\DTOs\GoogleLoginRequest;
use App\User\Application\DTOs\GoogleLoginResponse;
use App\User\Domain\Entities\User;

class LoginWithGoogleUseCase
{
    private GoogleAuthServiceInterface $googleAuthService;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        GoogleAuthServiceInterface $googleAuthService,
        UserRepositoryInterface $userRepository
    ) {
        $this->googleAuthService = $googleAuthService;
        $this->userRepository = $userRepository;
    }

    public function execute(GoogleLoginRequest $request): GoogleLoginResponse
    {
        try {
            // 1. Validate request
            $errors = $request->validate();
            if (!empty($errors)) {
                return new GoogleLoginResponse(false, 'Validation failed', null, null, $errors);
            }

            // 2. Authenticate with Google
            $googleUser = $this->googleAuthService->authenticate($request->code);
            
            if (!$googleUser) {
                return new GoogleLoginResponse(false, 'Failed to authenticate with Google');
            }

            // 3. Find or create user
            $user = $this->userRepository->findOrCreateFromGoogle($googleUser);

            if (!$user) {
                return new GoogleLoginResponse(false, 'Failed to create or find user');
            }

            // 4. Determine redirect URL based on role
            $redirectUrl = $this->getRedirectUrl($user->getRoleName());

            // 5. Create session
            $this->createSession($user);

            return new GoogleLoginResponse(
                true,
                'Successfully logged in with Google',
                $user,
                $redirectUrl
            );

        } catch (\Exception $e) {
            return new GoogleLoginResponse(
                false,
                'Google login failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get redirect URL based on role name
     * ✅ Fixed: Accepts string, not User object
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
     * Create session for the user
     */
    private function createSession(User $user): void
    {
        $_SESSION['user_id'] = $user->getId()->getValue();
        $_SESSION['user_name'] = $user->getName();
        $_SESSION['user_email'] = $user->getEmail()->getValue();
        $_SESSION['user_role'] = $user->getRoleName();
        $_SESSION['role_id'] = $user->getRoleId();
        $_SESSION['user_verified'] = true; // Google accounts are verified
        $_SESSION['login_method'] = 'google';
        $_SESSION['google_avatar'] = $user->getAvatar();
    }
}