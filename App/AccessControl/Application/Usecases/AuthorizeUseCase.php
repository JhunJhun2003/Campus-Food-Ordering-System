<?php
declare(strict_types=1);

namespace App\AccessControl\Application\Usecases;

use App\AccessControl\Domain\Services\AuthorizationServiceInterface;

class AuthorizeUseCase
{
    private AuthorizationServiceInterface $authorizationService;

    public function __construct(AuthorizationServiceInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    public function authorize(string $permission): void
    {
        $userId = $this->authorizationService->getCurrentUserId();
        $this->authorizationService->authorize($userId, $permission);
    }

    public function authorizeAny(array $permissions): void
    {
        $userId = $this->authorizationService->getCurrentUserId();
        $this->authorizationService->authorizeAny($userId, $permissions);
    }

    public function authorizeAll(array $permissions): void
    {
        $userId = $this->authorizationService->getCurrentUserId();
        $this->authorizationService->authorizeAll($userId, $permissions);
    }

    public function authorizeResource(int $resourceUserId, string $permission = 'view_orders'): void
    {
        $this->authorizationService->authorizeResource($resourceUserId, $permission);
    }

    public function hasPermission(string $permission): bool
    {
        $userId = $this->authorizationService->getCurrentUserId();
        return $this->authorizationService->hasPermission($userId, $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        $userId = $this->authorizationService->getCurrentUserId();
        return $this->authorizationService->hasAnyPermission($userId, $permissions);
    }

    public function isAdmin(): bool
    {
        $userId = $this->authorizationService->getCurrentUserId();
        return $this->authorizationService->isAdmin($userId);
    }

    public function isStaff(): bool
    {
        $userId = $this->authorizationService->getCurrentUserId();
        return $this->authorizationService->isStaff($userId);
    }

    public function getCurrentUserId(): int
    {
        return $this->authorizationService->getCurrentUserId();
    }

    public function getUserRole(): ?string
    {
        $userId = $this->authorizationService->getCurrentUserId();
        return $this->authorizationService->getUserRole($userId);
    }
}