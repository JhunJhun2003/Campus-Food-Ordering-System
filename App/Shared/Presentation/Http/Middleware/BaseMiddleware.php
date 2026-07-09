<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Middleware;

use App\User\Presentation\Http\Controllers\UserController;

/**
 * Base Middleware
 * Provides common functionality for all middleware
 * 
 * @package App\Shared\Presentation\Http\Middleware
 */
abstract class BaseMiddleware implements MiddlewareInterface
{
    protected UserController $userController;

    public function __construct()
    {
        $this->userController = getUserController();
    }

    /**
     * Redirect to a URL with optional message
     */
    protected function redirect(string $url, string $message = ''): void
    {
        if (!empty($message)) {
            $_SESSION['error'] = $message;
        }
        header('Location: ' . $url);
        exit();
    }

    /**
     * Get current authenticated user
     */
    protected function getCurrentUser(): ?array
    {
        return $this->userController->getCurrentUser();
    }

    /**
     * Check if user is logged in
     */
    protected function isLoggedIn(): bool
    {
        return $this->userController->isLoggedIn();
    }

    /**
     * Check if user's email is verified
     */
    protected function isVerified(): bool
    {
        return $this->userController->isVerified();
    }

    /**
     * Check if user has a specific permission
     */
    protected function hasPermission(string $permission): bool
    {
        return userHasPermission($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    protected function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    protected function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
}