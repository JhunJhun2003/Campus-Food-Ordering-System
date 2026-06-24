<?php
namespace App\User\Domain\Repositories;

use App\User\Domain\Entities\User;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function findAll(): array;
    public function delete(UserId $id): void;
    public function exists(Email $email): bool;
}