<?php
namespace PuneMirror\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [strtoupper($method), $pattern, $handler];
    }

    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    public function delete(string $pattern, callable $handler): void { $this->add('DELETE', $pattern, $handler); }
    public function patch(string $pattern, callable $handler): void { $this->add('PATCH', $pattern, $handler); }

    public function dispatch(Request $request): never
    {
        foreach ($this->routes as [$method, $pattern, $handler]) {
            if ($method !== $request->method && !($request->method === 'HEAD' && $method === 'GET')) continue;
            $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $request->path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($request, $params);
                exit;
            }
        }
        if (str_starts_with($request->path, '/api/')) Response::error('NOT_FOUND', 'Route not found', 404);
        Response::html('<h1>404</h1><p>Page not found.</p>', 404);
    }
}
