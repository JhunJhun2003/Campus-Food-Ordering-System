<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Staff Access Middleware
 * Ensures user is staff, admin, or has staff-like permissions
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class StaffAccessMiddleware extends BaseMiddleware
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl = '/Campus-Food-Ordering-System/view/customer/dashboard.php')
    {
        parent::__construct();
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/Campus-Food-Ordering-System/view/entrance/login.php', 'Please login first.');
        }

        // Use the helper to check if user has staff or admin access
        if (!\isStaff() && !\isAdminLike() && !\hasStaffPermissions()) {
            $this->redirect($this->redirectUrl, 'You do not have staff access.');
        }

        return $next($request);
    }
}
