<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

/**
 * Middleware Factory
 * Creates middleware instances with configuration
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class MiddlewareFactory
{
    /**
     * Create auth middleware
     */
    public static function auth(string $redirectUrl = '/view/entrance/login.php'): AuthMiddleware
    {
        return new AuthMiddleware($redirectUrl);
    }

    /**
     * Create guest middleware
     */
    public static function guest(string $redirectUrl = '/view/customer/dashboard.php'): GuestMiddleware
    {
        return new GuestMiddleware($redirectUrl);
    }

    /**
     * Create verified middleware
     */
    public static function verified(string $redirectUrl = '/view/entrance/verify-email.php'): VerifiedMiddleware
    {
        return new VerifiedMiddleware($redirectUrl);
    }

    /**
     * Create role middleware
     */
    public static function role(array $roles, string $redirectUrl = '/view/customer/dashboard.php'): RoleMiddleware
    {
        return new RoleMiddleware($roles, $redirectUrl);
    }

    /**
     * Create permission middleware
     */
    public static function permission(string $permission, string $redirectUrl = '/view/customer/dashboard.php'): PermissionMiddleware
    {
        return new PermissionMiddleware($permission, $redirectUrl);
    }

    /**
     * Create admin middleware
     */
    public static function admin(string $redirectUrl = '/view/customer/dashboard.php'): RoleMiddleware
    {
        return new RoleMiddleware(['admin'], $redirectUrl);
    }

    /**
     * Create staff middleware
     */
    public static function staff(string $redirectUrl = '/view/customer/dashboard.php'): RoleMiddleware
    {
        return new RoleMiddleware(['admin', 'staff'], $redirectUrl);
    }
}