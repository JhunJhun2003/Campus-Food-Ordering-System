<?php
declare(strict_types=1);

namespace App\Kernel;

use App\AccessControl\Presentation\Http\Middleware\MiddlewareFactory;
use App\Shared\Presentation\Http\Middleware\MiddlewareStack;

/**
 * HTTP Kernel
 * Defines middleware stacks for different route groups
 * 
 * @package App\Kernel
 */
class HttpKernel
{
    /**
     * Global middleware - runs on every request
     */
    public static function global(): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        // Add global middleware here if needed
        // $stack->add(MiddlewareFactory::something());
        return $stack;
    }

    /**
     * Customer middleware stack - auth + verified
     */
    public static function customer(): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::auth());
        $stack->add(MiddlewareFactory::verified());
        return $stack;
    }

    /**
     * Guest middleware stack - redirects logged-in users
     */
    public static function guest(): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::guest());
        return $stack;
    }

    /**
     * Admin middleware stack - auth + verified + admin role
     */
    public static function admin(): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::auth());
        $stack->add(MiddlewareFactory::verified());
        $stack->add(MiddlewareFactory::admin());
        return $stack;
    }

    /**
     * Staff middleware stack - auth + verified + staff role
     */
    public static function staff(): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::auth());
        $stack->add(MiddlewareFactory::verified());
        $stack->add(MiddlewareFactory::staff());
        return $stack;
    }

    /**
     * Permission middleware stack - auth + verified + permission
     */
    public static function withPermission(string $permission): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::auth());
        $stack->add(MiddlewareFactory::verified());
        $stack->add(MiddlewareFactory::permission($permission));
        return $stack;
    }

    /**
     * Role middleware stack - auth + verified + role
     */
    public static function withRole(array $roles): MiddlewareStack
    {
        $stack = new MiddlewareStack();
        $stack->add(MiddlewareFactory::auth());
        $stack->add(MiddlewareFactory::verified());
        $stack->add(MiddlewareFactory::role($roles));
        return $stack;
    }
}