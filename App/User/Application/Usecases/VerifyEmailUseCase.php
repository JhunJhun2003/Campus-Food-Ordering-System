<?php
namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Infrastructure\Repositories\EmailVerificationRepository;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\UserId;

class VerifyEmailUseCase
{
    private UserRepositoryInterface $userRepository;
    private EmailVerificationRepository $verificationRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailVerificationRepository $verificationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->verificationRepository = $verificationRepository;
    }

    public function execute(string $email, string $code): array
    {
        // Validate email
        try {
            $emailObj = new Email($email);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        // Check if already verified
        if ($this->verificationRepository->isEmailVerified($email)) {
            return ['success' => false, 'message' => 'Email already verified.'];
        }

        // Get verification record
        $verification = $this->verificationRepository->getVerification($email, $code);
        
        if (!$verification) {
            return ['success' => false, 'message' => 'Invalid or expired verification code.'];
        }

        // Mark as verified
        $verified = $this->verificationRepository->markAsVerified(
            $verification['id'],
            $verification['user_id']
        );

        if (!$verified) {
            return ['success' => false, 'message' => 'Failed to verify email. Please try again.'];
        }

        // Delete the verification record (optional)
        $this->verificationRepository->deleteVerification($verification['user_id']);

        return ['success' => true, 'message' => 'Email verified successfully!'];
    }
}