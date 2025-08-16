<?php

namespace FrankenCms\Services;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

readonly class RouteHandler
{
    public function __construct(private Router $router) {}

    public function shouldHandleRoute(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $routes = $this->getMethodRoutes($request->method());

        // If there's a registered route (excluding catch-all),
        // let Laravel handle it by returning false
        return ! $routes->contains(function ($route) use ($path) {
            return $this->isMatchingRoute($route, $path);
        });
    }

    private function getMethodRoutes(string $method): Collection
    {
        return collect($this->router->getRoutes()->getRoutesByMethod()[$method] ?? []);
    }

    private function isMatchingRoute($route, string $path): bool
    {
        // Exclude catch-all routes
        if (str_ends_with($route->uri(), '{any}')) {
            return false;
        }

        // Check if the path matches the route pattern
        return preg_match($route->getCompiled()->getRegex(), $path);
    }
}
