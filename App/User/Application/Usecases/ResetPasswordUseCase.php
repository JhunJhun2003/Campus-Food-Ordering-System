<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\UserRepositoryInterface;
use App\User\Domain\Repositories\PasswordResetRepositoryInterface;
use App\User\Domain\ValueObjects\Email;
use App\User\Domain\ValueObjects\Password;
use App\User\Domain\ValueObjects\UserId;
use App\User\Application\DTOs\ResetPasswordRequest;

class ResetPasswordUseCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordResetRepositoryInterface $passwordResetRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PasswordResetRepositoryInterface $passwordResetRepository
    ) {
        $this->userRepository = $userRepository;
        $this->passwordResetRepository = $passwordResetRepository;
    }

    public function execute(ResetPasswordRequest $request): array
    {
        // 1. Validate request
        $errors = $request->validate();
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }

        // 2. Find reset record by code
        $passwordReset = $this->passwordResetRepository->findByCode($request->code);

        if (!$passwordReset) {
            return [
                'success' => false,
                'message' => 'Invalid reset code.',
                'errors' => ['code' => 'Invalid reset code']
            ];
        }

        // 3. Check if code is valid
        if (!$passwordReset->isValid()) {
            return [
                'success' => false,
                'message' => 'Reset code is invalid or expired.',
                'errors' => ['code' => 'Invalid or expired code']
            ];
        }

        // 4. Check if email matches
        if ($passwordReset->getEmail() !== $request->email) {
            return [
                'success' => false,
                'message' => 'Invalid reset code for this email.',
                'errors' => ['code' => 'Invalid code']
            ];
        }

        // 5. Find user
        $user = $this->userRepository->findById(new UserId($passwordReset->getUserId()));
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found.',
                'errors' => ['email' => 'User not found']
            ];
        }

        // 6. Update password
        $newPassword = new Password($request->newPassword);
        $user->changePassword($newPassword);
        $this->userRepository->save($user);

        // 7. Mark reset code as used
        $this->passwordResetRepository->markAsUsed($passwordReset->getId());

        // 8. Delete expired reset codes
        $this->passwordResetRepository->deleteExpired();

        return [
            'success' => true,
            'message' => 'Password reset successfully! You can now login with your new password.'
        ];
    }
}