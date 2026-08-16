<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Admin Access Middleware
 * Ensures user is an admin or has admin-like permissions
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class AdminAccessMiddleware extends BaseMiddleware
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

        // Use the helper to check if user has admin access
        // This supports dynamic roles with admin permissions
        if (!\isAdminLike()) {
            $this->redirect($this->redirectUrl, 'You do not have admin access.');
        }

        return $next($request);
    }
}
