<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Traits;

use App\AccessControl\Application\Usecases\AuthorizeUseCase;
use App\AccessControl\Infrastructure\Services\AuthorizationService;

trait AuthorizesRequests
{
    private ?AuthorizeUseCase $authorizeUseCase = null;

    /**
     * Authorize a single permission
     */
    protected function authorize(string $permission): void
    {
        $this->getAuthorizeUseCase()->authorize($permission);
    }

    /**
     * Authorize any of the given permissions
     */
    protected function authorizeAny(array $permissions): void
    {
        $this->getAuthorizeUseCase()->authorizeAny($permissions);
    }

    /**
     * Authorize all of the given permissions
     */
    protected function authorizeAll(array $permissions): void
    {
        $this->getAuthorizeUseCase()->authorizeAll($permissions);
    }

    /**
     * Authorize resource access (user owns it or has permission)
     */
    protected function authorizeResource(int $resourceUserId, string $permission = 'view_orders'): void
    {
        $this->getAuthorizeUseCase()->authorizeResource($resourceUserId, $permission);
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->getAuthorizeUseCase()->hasPermission($permission);
    }

    /**
     * Check if user has any permission
     */
    protected function hasAnyPermission(array $permissions): bool
    {
        return $this->getAuthorizeUseCase()->hasAnyPermission($permissions);
    }

    /**
     * Get or create AuthorizeUseCase instance
     */
    private function getAuthorizeUseCase(): AuthorizeUseCase
    {
        if ($this->authorizeUseCase === null) {
            $this->authorizeUseCase = new AuthorizeUseCase(
                new AuthorizationService()
            );
        }
        return $this->authorizeUseCase;
    }
}