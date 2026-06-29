<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Application\DTOs\LoginUserRequest;
use App\User\Application\DTOs\LoginUserResponse;
use InvalidArgumentException;

class LoginUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(LoginUserRequest $request): LoginUserResponse
    {
        try {
            // Validate email
            if (empty($request->email) || !filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                return new LoginUserResponse(false, 'Please enter a valid email address', null);
            }

            // Validate password
            if (empty($request->password)) {
                return new LoginUserResponse(false, 'Please enter your password', null);
            }

            // Find user by email
            $email = new Email($request->email);
            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                return new LoginUserResponse(false, 'Invalid email or password', null);
            }

            // Verify password
            if (!$user->getPassword()->verify($request->password)) {
                return new LoginUserResponse(false, 'Invalid email or password', null);
            }

            // Determine redirect URL based on role name
            $roleName = $user->getRoleName();
            $redirectUrl = match ($roleName) {
                'admin' => '/Campus-Food-Ordering-System/view/admin/admin-dashboard.php',
                'staff' => '/Campus-Food-Ordering-System/view/staff/staff-dashboard.php',
                default => '/Campus-Food-Ordering-System/view/customer/dashboard.php'
            };

            return new LoginUserResponse(
                true,
                'Login successful! Welcome back, ' . $user->getName() . '!',
                $user,
                $redirectUrl
            );

        } catch (InvalidArgumentException $e) {
            return new LoginUserResponse(false, $e->getMessage(), null);
        } catch (\Exception $e) {
            return new LoginUserResponse(false, 'An error occurred during login. Please try again.', null);
        }
    }
}