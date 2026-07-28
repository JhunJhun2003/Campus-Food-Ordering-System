<?php
declare(strict_types=1);

namespace App\User\Application\Usecases;

use App\User\Domain\Repositories\PasswordResetRepositoryInterface;
use App\User\Application\DTOs\VerifyResetCodeRequest;

class VerifyResetCodeUseCase
{
    private PasswordResetRepositoryInterface $passwordResetRepository;

    public function __construct(PasswordResetRepositoryInterface $passwordResetRepository)
    {
        $this->passwordResetRepository = $passwordResetRepository;
    }

    public function execute(VerifyResetCodeRequest $request): array
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
            if ($passwordReset->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'Reset code has expired. Please request a new one.',
                    'errors' => ['code' => 'Code expired']
                ];
            }
            return [
                'success' => false,
                'message' => 'Reset code has already been used.',
                'errors' => ['code' => 'Code already used']
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

        return [
            'success' => true,
            'message' => 'Reset code verified successfully.',
            'data' => [
                'user_id' => $passwordReset->getUserId(),
                'email' => $passwordReset->getEmail()
            ]
        ];
    }
}