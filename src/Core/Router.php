<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, $handler): void
    {
        $path = rtrim($path, '/') ?: '/';
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = $this->match($route['path'], $request->path);
            if ($params === null) {
                continue;
            }

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->$method($request, ...array_values($params));
                return;
            }

            $handler($request, ...array_values($params));
            return;
        }

        Response::html('<h1>404</h1><p>Страницата не е пронајдена.</p>', 404);
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $i => $part) {
            if (strpos($part, '{') === 0) {
                $name = trim($part, '{}');
                $params[$name] = $requestParts[$i];
            } elseif ($part !== $requestParts[$i]) {
                return null;
            }
        }

        return $params;
    }
}
