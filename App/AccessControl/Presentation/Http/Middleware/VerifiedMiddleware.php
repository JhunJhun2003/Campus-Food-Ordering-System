<?php
declare(strict_types=1);

namespace App\AccessControl\Presentation\Http\Middleware;

use App\Shared\Presentation\Http\Middleware\BaseMiddleware;

/**
 * Verified Middleware
 * Ensures user's email is verified
 * 
 * @package App\AccessControl\Presentation\Http\Middleware
 */
class VerifiedMiddleware extends BaseMiddleware
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl = '/view/entrance/verify-email.php')
    {
        parent::__construct();
        $this->redirectUrl = $redirectUrl;
    }

    public function handle(array $request, callable $next)
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/view/entrance/login.php', 'Please login first.');
        }

        if (!$this->isVerified()) {
            $this->redirect($this->redirectUrl, 'Please verify your email address.');
        }

        return $next($request);
    }
}