<?php
declare(strict_types=1);

namespace App\Router;

use App\Shared\Presentation\Http\Middleware\MiddlewareInterface;
use App\Shared\Presentation\Http\Middleware\MiddlewareStack;

/**
 * Route Class
 * Represents a single route with middleware support
 * 
 * @package App\Router
 */
class Route
{
    private string $method;
    private string $path;
    private $handler;
    private MiddlewareStack $middlewareStack;

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->middlewareStack = new MiddlewareStack();
    }

    /**
     * Add middleware to this route
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewareStack->add($middleware);
        return $this;
    }

    /**
     * Add a middleware stack to this route
     */
    public function withMiddleware(MiddlewareStack $stack): self
    {
        $this->middlewareStack = $stack;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getMiddlewareStack(): MiddlewareStack
    {
        return $this->middlewareStack;
    }

    /**
     * Check if route matches the request
     */
    public function matches(string $method, string $path): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $this->path);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $path) === 1;
    }

    /**
     * Extract parameters from the path
     */
    public function getParams(string $path): array
    {
        $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_]+)', $this->path);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';

        preg_match($pattern, $path, $matches);
        array_shift($matches);
        return $matches;
    }
}