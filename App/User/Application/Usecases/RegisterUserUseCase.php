<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Entities\User;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\DTOs\RegisterUserResponse;

class RegisterUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(RegisterUserRequest $request): RegisterUserResponse
    {
        $errors = [];

        // Validate name
        if (empty($request->name) || strlen($request->name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        // Validate email
        try {
            $email = new Email($request->email);
        } catch (\Exception $e) {
            $errors['email'] = $e->getMessage();
        }

        // Validate password
        try {
            $password = new Password($request->password);
        } catch (\Exception $e) {
            $errors['password'] = $e->getMessage();
        }

        // Check if user exists
        if (isset($email) && $this->userRepository->exists($email)) {
            $errors['email'] = 'Email already registered';
        }

        // Return errors if any
        if (!empty($errors)) {
            return new RegisterUserResponse(false, 'Validation failed', null, $errors);
        }

        // Create user
        $user = new User(
            new UserId(null), // Auto-increment ID
            $request->name,
            $email,
            $password,
            $request->phone,
            'user' // Default role
        );

        // Save user
        $this->userRepository->save($user);

        return new RegisterUserResponse(
            true,
            'Registration successful! Please login.',
            $user
        );
    }
}