<?php
declare(strict_types=1);

namespace App\AccessControl\Domain\Services;

interface AuthorizationServiceInterface
{
    public function hasPermission(int $userId, string $permission): bool;
    public function hasAnyPermission(int $userId, array $permissions): bool;
    public function hasAllPermissions(int $userId, array $permissions): bool;
    public function authorize(int $userId, string $permission): void;
    public function authorizeAny(int $userId, array $permissions): void;
    public function authorizeAll(int $userId, array $permissions): void;
    public function isAdmin(int $userId): bool;
    public function isStaff(int $userId): bool;
    public function getCurrentUserId(): int;
    public function isResourceOwner(int $resourceUserId): bool;
    public function authorizeResource(int $resourceUserId, string $permission = 'view_orders'): void;
    public function getUserRole(int $userId): ?string;
}