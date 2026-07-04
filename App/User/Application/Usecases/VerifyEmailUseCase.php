<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\UserId;

class VerifyEmailUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(string $email, string $code): array
    {
        try {
            $emailObj = new Email($email);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        // Find user by email
        $user = $this->userRepository->findByEmail($emailObj);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if ($user->isVerified()) {
            return ['success' => false, 'message' => 'Email already verified.'];
        }

        // Check if verification code is valid
        if (!$user->isVerificationCodeValid($code)) {
            return ['success' => false, 'message' => 'Invalid or expired verification code.'];
        }

        // Verify the user
        $user->verifyEmail();
        
        // Save user
        $this->userRepository->save($user);

        // ✅ REMOVED: Auto-login session setting
        // No session is set here - user must login manually

        return [
            'success' => true,
            'message' => 'Email verified successfully! Please login to continue.',
            'user' => $user
        ];
    }
}