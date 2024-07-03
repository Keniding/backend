<?php

namespace Router;

class Router implements IRouter
{
    protected array $routes = [];

    public function get($uri, $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function delete($uri, $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    public function addRoute($method, $uri, $action): void
    {
        $route = strtoupper($method) . ' ' . rtrim($uri, '/');
        $this->routes[$route] = $action;
    }

    public function dispatch($method, $uri)
    {
        $route = strtoupper($method) . ' ' . rtrim($uri, '/');

        foreach ($this->routes as $routePattern => $action) {
            $pattern = preg_replace(
                '/\{[^\}]+\}/',
                '([^\/]+)',
                str_replace('/', '\/', $routePattern)
            );
            $pattern = '/^' . $pattern . '$/';

            if (preg_match($pattern, $route, $matches)) {
                array_shift($matches);
                return call_user_func_array($action, $matches);
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }

    public function header(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public function request(): void
    {
        $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $request_method = $_SERVER['REQUEST_METHOD'];
        $this->dispatch($request_method, $request_uri);
    }

    public function input()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    public function error(): void
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['error' => 'Invalid JSON input']);
            return;
        }
    }
}
