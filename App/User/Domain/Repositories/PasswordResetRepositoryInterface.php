<?php
declare(strict_types=1);

namespace App\User\Domain\Repositories;

use App\User\Domain\Entities\PasswordReset;

interface PasswordResetRepositoryInterface
{
    public function create(PasswordReset $passwordReset): int;
    public function findByCode(string $code): ?PasswordReset;
    public function findByUserId(int $userId): ?PasswordReset;
    public function deleteByUserId(int $userId): bool;
    public function deleteExpired(): int;
    public function markAsUsed(int $id): bool;
}