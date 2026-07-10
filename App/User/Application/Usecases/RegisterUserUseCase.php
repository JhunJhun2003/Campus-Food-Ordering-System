<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Entities\User;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\DTOs\RegisterUserResponse;
use Inc\Database;

class RegisterUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(RegisterUserRequest $request): RegisterUserResponse
    {
        $db = Database::getConnection();
        
        try {
            // ✅ Start transaction - User creation and related operations
            $db->beginTransaction();
            
            $errors = [];
            $email = null;
            $password = null;

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
            if ($email !== null && $this->userRepository->exists($email)) {
                $errors['email'] = 'Email already registered';
            }

            // Return errors if any
            if (!empty($errors)) {
                $db->rollBack();
                return new RegisterUserResponse(false, 'Validation failed', null, $errors);
            }

            // Get role_id from repository
            $roleId = $this->userRepository->getRoleId('user');

            // Create user (not verified yet)
            $user = new User(
                new UserId(null),
                $roleId,
                'user',
                $request->name,
                $email,
                $password,
                $request->phone,
                null, // address
                false, // isVerified
                null // emailVerifiedAt
            );

            // Save user and get the ID
            $userId = $this->userRepository->save($user);

            // ✅ Generate and save verification code (if applicable)
            // $this->userRepository->setVerificationCode($userId, $this->generateVerificationCode(), 10);

            // Create a new User object with the ID
            $userWithId = new User(
                new UserId($userId),
                $roleId,
                'user',
                $request->name,
                $email,
                $password,
                $request->phone,
                null,
                false,
                null
            );

            // ✅ All operations succeeded
            $db->commit();

            return new RegisterUserResponse(
                true,
                'Registration successful! Please verify your email.',
                $userWithId
            );
            
        } catch (\Exception $e) {
            // ✅ Rollback on any error
            $db->rollBack();
            
            return new RegisterUserResponse(
                false,
                'Registration failed: ' . $e->getMessage(),
                null,
                ['error' => $e->getMessage()]
            );
        }
    }
}