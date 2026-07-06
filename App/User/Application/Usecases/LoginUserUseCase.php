<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\DTOs\LoginUserResponse;

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

        // Check if user is verified
        if (!$user->isVerified()) {
            return new LoginUserResponse(false, 'Please verify your email first. Check your inbox for the verification code.', null);
        }

        // Verify password
        if (!$user->getPassword()->verify($request->password)) {
            return new LoginUserResponse(false, 'Invalid credentials', null);
        }

        // Determine redirect URL based on role
        $roleName = strtolower($user->getRoleName());
        
        // Check if staff-dashboard.php exists, if not redirect to admin orders
        $redirectUrl = match ($roleName) {
            'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
            'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
            default => '/Campus-Food-Ordering-System/view/customer/dashboard.php'
        };

        return new LoginUserResponse(
            true,
            'Login successful!',
            $user,
            $redirectUrl
        );
    }
}