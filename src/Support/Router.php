<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = array_map('urldecode', $matches);
            call_user_func_array($route['handler'], $params);
            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][] = [
            'path' => $path,
            'handler' => $handler,
        ];
    }
}
