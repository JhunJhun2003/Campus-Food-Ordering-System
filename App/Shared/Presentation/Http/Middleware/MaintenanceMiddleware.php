<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\MiddlewareInterface;
use App\AccessControl\Infrastructure\Services\AuthorizationService;
use Inc\Database;

class MaintenanceMiddleware implements MiddlewareInterface
{
    private AuthorizationService $authService;
    private bool $isMaintenanceMode;

    public function __construct()
    {
        $this->authService = new AuthorizationService();
        $this->isMaintenanceMode = $this->checkMaintenanceMode();
    }

    public function handle(array $request, callable $next)
    {
        // If maintenance is off, proceed to next middleware
        if (!$this->isMaintenanceMode) {
            return $next($request);
        }

        // ✅ Admin or admin-like users can always access everything
        if ($this->isAdminLike()) {
            return $next($request);
        }

        $currentUserId = $this->authService->getCurrentUserId();

        // ✅ If user is logged in (customer or staff), force logout
        if ($currentUserId > 0) {
            $this->forceLogoutAndRedirect();
            return null;
        }

        // ✅ Guest trying to access login or register pages
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if ($this->isAuthPage($currentPath)) {
            $this->redirectToMaintenancePage();
            return null;
        }

        // ✅ Guest trying to access customer/staff pages
        if ($this->isProtectedPage($currentPath)) {
            $this->redirectToMaintenancePage();
            return null;
        }

        return $next($request);
    }

    /**
     * Check if maintenance mode is enabled
     */
    private function checkMaintenanceMode(): bool
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT setting_value 
                FROM settings 
                WHERE setting_key = 'maintenance_mode'
            ");
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result && (int) $result['setting_value'] === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool
    {
        $userId = $this->authService->getCurrentUserId();
        if ($userId === 0) {
            return false;
        }
        return $this->authService->isAdmin($userId);
    }

    /**
     * Check if current user is admin-like (admin or has admin permissions)
     */
    private function isAdminLike(): bool
    {
        $userId = $this->authService->getCurrentUserId();
        if ($userId === 0) {
            return false;
        }

        if ($this->authService->isAdmin($userId)) {
            return true;
        }

        return $this->authService->hasAnyPermission($userId, [
            'view_dashboard',
            'manage_users',
            'manage_menu',
            'manage_orders',
            'manage_settings',
            'view_reports',
        ]);
    }

    /**
     * Check if current page is an auth page (login/register)
     */
    private function isAuthPage(string $path): bool
    {
        $authPaths = [
            '/login',
            '/register',
            '/entrance/login.php',
            '/entrance/register.php',
            '/Campus-Food-Ordering-System/view/entrance/login.php',
            '/Campus-Food-Ordering-System/view/entrance/register.php'
        ];

        foreach ($authPaths as $authPath) {
            if (strpos($path, $authPath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current page is a protected page (customer/staff)
     */
    private function isProtectedPage(string $path): bool
    {
        $protectedPaths = [
            '/dashboard',
            '/cart',
            '/checkout',
            '/orders',
            '/profile',
            '/staff',
            '/customer'
        ];

        // Admin pages are accessible (already handled by isAdmin check)
        if (strpos($path, '/admin') !== false) {
            return false;
        }

        foreach ($protectedPaths as $protectedPath) {
            if (strpos($path, $protectedPath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Force logout and redirect to maintenance page
     */
    private function forceLogoutAndRedirect(): void
    {
        $_SESSION = [];
        session_destroy();
        
        $_SESSION['maintenance_message'] = 'The system is currently under maintenance. Please try again later.';
        $this->redirectToMaintenancePage();
    }

    /**
     * Redirect to maintenance page
     */
    private function redirectToMaintenancePage(): void
    {
        $url = '/Campus-Food-Ordering-System/view/maintenance/maintenance.php';
        if (headers_sent()) {
            echo '<script>window.location.href="' . $url . '";</script>';
            exit();
        }
        header('Location: ' . $url);
        exit();
    }
}