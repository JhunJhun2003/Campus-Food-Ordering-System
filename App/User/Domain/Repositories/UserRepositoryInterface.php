<?php
namespace App\User\Domain\Repositories;

use App\User\Domain\Entities\User;
use App\User\Domain\ValueObjects\UserId;
use App\User\Domain\ValueObjects\Email;

interface UserRepositoryInterface
{
    // ===== USER CRUD =====
    public function save(User $user): void;
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function findAll(): array;
    public function delete(UserId $id): void;
    public function exists(Email $email): bool;
    
    // ===== ROLE =====
    public function getRoleId(string $roleName): int;
    
    // ===== ADMIN DASHBOARD =====
    public function getTotalUsers(): int;
    public function getTotalFoods(): int;
    public function getTotalOrders(): int;
    public function getPendingOrders(): int;
    public function getRecentOrders(int $limit = 5): array;
    
    // ===== REPORTS =====
    public function getTotalRevenue(): float;
    public function getCompletedOrders(): int;
    public function getMonthlyRevenue(int $months = 6): array;
    public function getOrderStats(): array;
    
    // ===== SETTINGS =====
    public function getAllSettings(): array;
    public function getSettingsByGroup(string $group): array;
    public function getSetting(string $key): ?string;
    public function updateSetting(string $key, string $value): bool;
    public function updateSettings(array $settings): array;
    
    // ===== ADMIN USER MANAGEMENT =====
    public function getAllRoles(): array;
    public function emailExists(string $email): bool;
    public function createUser(string $name, string $email, string $password, string $phone, int $roleId): int;
    public function deleteUser(int $userId): bool;
    public function updateUser(int $userId, array $data): bool;
    public function getUserForEdit(int $userId): ?array;
public function emailExistsExcluding(string $email, int $userId): bool;


}