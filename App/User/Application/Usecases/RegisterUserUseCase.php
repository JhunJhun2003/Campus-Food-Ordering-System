<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Entities\User;
use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use App\User\Application\DTOs\RegisterUserRequest;
use App\User\Application\DTOs\RegisterUserResponse;
use App\Security\Infrastructure\Services\GoogleRecaptchaService;
use Inc\Database;

class RegisterUserUseCase
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

    public function execute(RegisterUserRequest $request): RegisterUserResponse
    {
        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();
            
            // ✅ Verify reCAPTCHA FIRST
            if ($this->recaptchaService->isEnabled()) {
                if (!$this->recaptchaService->verify($request->captchaToken)) {
                    return new RegisterUserResponse(
                        false, 
                        'Please complete the reCAPTCHA verification.', 
                        null,
                        ['captcha' => 'reCAPTCHA verification failed.']
                    );
                }
            }
            
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

            // Create user
            $user = new User(
                new UserId(null),
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

            // Save user
            $userId = $this->userRepository->save($user);

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

            $db->commit();

            return new RegisterUserResponse(
                true,
                'Registration successful! Please verify your email.',
                $userWithId
            );
            
        } catch (\Exception $e) {
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