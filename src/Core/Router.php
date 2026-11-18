<?php
declare(strict_types=1);

namespace App\Core;

use App\Support\Logger;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,regex:string,params:string[],handler:mixed}> */
    private array $routes = [];

    public function get(string $pattern, mixed $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, mixed $handler): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $pattern
        );

        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $args = [];
            foreach ($route['params'] as $name) {
                $args[$name] = $matches[$name] ?? null;
            }
            $this->invoke($route['handler'], $args);
            return;
        }

        Logger::warning('ROUTE_NOT_FOUND', ['method' => $method, 'path' => $path]);
        http_response_code(404);
        View::render('errors/404', ['pageTitle' => 'Halaman tidak ditemukan'], 'layout_public');
    }

    private function invoke(mixed $handler, array $args): void
    {
        if (is_callable($handler)) {
            $handler($args);
            return;
        }
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->{$method}($args);
            return;
        }
        throw new \RuntimeException('Handler rute tidak valid.');
    }
}
