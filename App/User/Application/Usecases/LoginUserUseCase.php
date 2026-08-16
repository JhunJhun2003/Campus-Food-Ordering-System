<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\DTOs\LoginUserResponse;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use App\Security\Infrastructure\Services\GoogleRecaptchaService;
use Inc\Database;

class LoginUserUseCase
{
    private UserRepositoryInterface $userRepository;
    private GoogleRecaptchaService $recaptchaService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        GoogleRecaptchaService $recaptchaService
    ) {
        $this->userRepository = $userRepository;
        $this->recaptchaService = $recaptchaService;
    }

    public function execute(LoginUserRequest $request): LoginUserResponse
    {
        // Validate email
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return new LoginUserResponse(false, 'Invalid email format', null);
        }

        // ✅ Verify reCAPTCHA FIRST
        if ($this->recaptchaService->isEnabled()) {
            if (!$this->recaptchaService->verify($request->captchaToken)) {
                return new LoginUserResponse(
                    false,
                    'Please complete the reCAPTCHA verification.',
                    null
                );
            }
        }

        // Find user by email
        $email = new Email($request->email);
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return new LoginUserResponse(false, 'Invalid credentials', null);
        }

        // Verify password
        if (!$user->getPassword()->verify($request->password)) {
            return new LoginUserResponse(false, 'Invalid credentials', null);
        }

        $redirectUrl = $this->getRedirectUrl($user->getRoleName(), $user->getId()->getValue());

        return new LoginUserResponse(
            true,
            'Login successful!',
            $user,
            $redirectUrl
        );
    }

    private function getRedirectUrl(string $roleName, int $userId): string
    {
        $role = strtolower($roleName);

        if ($role === 'customer') {
            return '/Campus-Food-Ordering-System/view/customer/dashboard.php';
        }

        if ($role === 'admin' || (function_exists('isAdminLike') && \isAdminLike($userId))) {
            return '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php';
        }

        if ($role === 'staff' || (function_exists('hasStaffPermissions') && \hasStaffPermissions($userId))) {
            return '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php';
        }

        return '/Campus-Food-Ordering-System/view/customer/dashboard.php';
    }
}