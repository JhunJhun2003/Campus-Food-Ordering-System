<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\DTOs\LoginUserResponse;
use App\AccessControl\Infrastructure\Repositories\AccessControlRepository;
use App\AccessControl\Application\Usecases\CheckPermissionUseCase;
use Inc\Database;

class LoginUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(LoginUserRequest $request): LoginUserResponse
    {
        // Validate email
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return new LoginUserResponse(false, 'Invalid email format', null);
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
            // ignore and fallback to customer dashboard
        }

        return '/Campus-Food-Ordering-System/view/customer/dashboard.php';
    }
}
