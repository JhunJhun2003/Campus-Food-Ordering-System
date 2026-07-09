<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Middleware;

/**
 * Middleware Interface
 * Defines the contract for all middleware
 * 
 * @package App\Shared\Presentation\Http\Middleware
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     * 
     * @param array $request Request data (session, get, post, etc.)
     * @param callable $next Next middleware to execute
     * @return mixed
     */
    public function handle(array $request, callable $next);
}