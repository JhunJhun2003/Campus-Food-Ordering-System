<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Role Middleware
 * Ensures user has required role
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class RoleMiddleware extends BaseMiddleware
{
    private array $allowedRoles;
    private string $redirectUrl;

    public function __construct(array $allowedRoles, string $redirectUrl = '/view/customer/dashboard.php')
    {
        parent::__construct();
        $this->allowedRoles = $allowedRoles;
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/view/entrance/login.php', 'Please login first.');
        }

        $user = $this->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        if (!in_array($userRole, $this->allowedRoles)) {
            $this->redirect($this->redirectUrl, 'You do not have the required role.');
        }

        return $next($request);
    }
}