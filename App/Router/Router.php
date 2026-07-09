<?php
declare(strict_types=1);

namespace App\Router;

use App\Shared\Presentation\Http\Middleware\MiddlewareStack;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler): Route
    {
        $route = new Route('GET', $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function post(string $path, $handler): Route
    {
        $route = new Route('POST', $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function put(string $path, $handler): Route
    {
        $route = new Route('PUT', $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function delete(string $path, $handler): Route
    {
        $route = new Route('DELETE', $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function dispatch(string $method, string $path, array $request = []): mixed
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                $params = $route->getParams($path);
                $handler = $route->getHandler();
                $middlewareStack = $route->getMiddlewareStack();

                $finalHandler = function ($request) use ($handler, $params) {
                    if (is_callable($handler)) {
                        return $handler(...array_merge($params, [$request]));
                    }
                    return $handler;
                };

                if (!$middlewareStack->isEmpty()) {
                    return $middlewareStack->handle($request, $finalHandler);
                }

                return $finalHandler($request);
            }
        }

        http_response_code(404);
        return ['error' => 'Route not found', 'status' => 404];
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}