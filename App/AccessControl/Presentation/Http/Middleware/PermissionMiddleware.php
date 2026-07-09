<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Permission Middleware
 * Ensures user has required permission
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class PermissionMiddleware extends BaseMiddleware
{
    private string $permission;
    private string $redirectUrl;

    public function __construct(string $permission, string $redirectUrl = '/view/customer/dashboard.php')
    {
        parent::__construct();
        $this->permission = $permission;
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/view/entrance/login.php', 'Please login first.');
        }

        if (!$this->hasPermission($this->permission)) {
            $this->redirect($this->redirectUrl, 'You do not have permission.');
        }

        return $next($request);
    }
}