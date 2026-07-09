<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Auth Middleware
 * Ensures user is authenticated
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class AuthMiddleware extends BaseMiddleware
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl = '/view/entrance/login.php')
    {
        parent::__construct();
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect($this->redirectUrl, 'Please login to access this page.');
        }

        return $next($request);
    }
}