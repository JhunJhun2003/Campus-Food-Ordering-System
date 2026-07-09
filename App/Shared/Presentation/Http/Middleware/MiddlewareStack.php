<?php
declare(strict_types=1);

namespace App\Shared\Presentation\Http\Middleware;

/**
 * Middleware Stack
 * Manages and executes middleware chain
 * 
 * @package App\Shared\Presentation\Http\Middleware
 */
class MiddlewareStack
{
    private array $middlewares = [];

    /**
     * Add a middleware to the stack
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Add multiple middleware to the stack
     */
    public function addMany(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
        return $this;
    }

    /**
     * Execute the middleware chain
     */
    public function handle(array $request, callable $final): mixed
    {
        $chain = $this->buildChain($final);
        return $chain($request);
    }

    /**
     * Build the middleware chain
     */
    private function buildChain(callable $final): callable
    {
        $chain = $final;
        
        // Reverse so first middleware executes first
        foreach (array_reverse($this->middlewares) as $middleware) {
            $chain = function($request) use ($middleware, $chain) {
                return $middleware->handle($request, $chain);
            };
        }

        return $chain;
    }

    /**
     * Check if stack has middleware
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }

    /**
     * Get all middleware in stack
     */
    public function getAll(): array
    {
        return $this->middlewares;
    }

    /**
     * Clear all middleware
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * Count middleware in stack
     */
    public function count(): int
    {
        return count($this->middlewares);
    }
}