<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Guest Middleware
 * Redirects authenticated users away from guest pages
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class GuestMiddleware extends BaseMiddleware
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl = '/view/customer/dashboard.php')
    {
        parent::__construct();
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if ($this->isLoggedIn()) {
            $user = $this->getCurrentUser();
            $role = $user['role'] ?? 'user';
            
            $redirectMap = [
                'admin' => '/view/admin/admin-dashboard.php',
                'staff' => '/view/staff/staff-dashboard.php',
                'user' => '/view/customer/dashboard.php'
            ];
            
            $this->redirect($redirectMap[$role] ?? $this->redirectUrl);
        }

        return $next($request);
    }
}