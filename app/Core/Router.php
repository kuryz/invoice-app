<?php

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

class Router
{
    private array $routes = [];
    protected ?array $lastRoute = null;
    private array $middlewares = [];
    private string $groupPrefix = '';
    private array $groupMiddlewares = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        // Temporarily store current prefix and middlewares
        $this->groupPrefix = $prefix;
        $this->groupMiddlewares = $middlewares;
        $callback($this);
        $this->groupPrefix = '';
        $this->groupMiddlewares = [];
    }

    // Modify addRoute to prepend group prefix and merge middlewares
    public function addRoute(string $method, string $path, array $handler, array $middlewares = [],?string $name = null): void
    {
        $path = $this->groupPrefix . $path;
        $middlewares = array_merge($this->groupMiddlewares, $middlewares);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'name' => $name
        ];
    }

    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function resolve(): void
{
    $method = $this->request->getMethod();
    $path = $this->request->getPath();

    // 👉 ADD THIS BLOCK: Strip out the live subfolder prefix
    // This turns "backend/api/v1/login" back into "/api/v1/login"
    if (str_starts_with($path, '/backend')) {
        $path = substr($path, 8); // Remove the first 8 characters (/backend)
    }
    // Ensure it always has a leading slash
    if (empty($path) || $path[0] !== '/') {
        $path = '/' . $path;
    }

    // Apply global middlewares
    $globalMiddlewares = [
        CorsMiddleware::class,
        JsonMiddleware::class,
    ];

    foreach ($globalMiddlewares as $middleware) {
        $instance = new $middleware();
        $instance->handle($this->request, $this->response);
    }

    foreach ($this->routes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }

        $params = $this->matchPath($route['path'], $path);
        $this->request->setRouteName($route['name'] ?? null);
        if ($params === null) {
            continue;
        }

        // Apply route middlewares
        $routeMiddlewares = array_merge($globalMiddlewares, $route['middlewares']);
        foreach ($routeMiddlewares as $middleware) {
            if (is_string($middleware)) {
                $instance = new $middleware();
                $instance->handle($this->request, $this->response);
            }
        }

        [$controllerClass, $methodName] = $route['handler'];
        $controller = new $controllerClass();

        if (!empty($params)) {
            $this->request->setParams($params);
        }

        // Capture and echo the controller output
        // $output = $controller->{$methodName}($this->request, $this->response);
        $output = $this->invokeController(
            $controller,
            $methodName,
            $params
        );
        if (is_string($output)) {
            echo $output;
        }

        return;
    }

    $this->response->json(['success' => false, 'message' => 'Route not found'], 404);
}

    private function invokeController(
        object $controller,
        string $methodName,
        array $params
    ): mixed {
        $reflection = new ReflectionMethod($controller, $methodName);

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof ReflectionNamedType
                ? $type->getName()
                : null;

            // Automatically inject Request
            if ($typeName === Request::class) {
                $arguments[] = $this->request;
                continue;
            }

            // Automatically inject Response
            if ($typeName === Response::class) {
                $arguments[] = $this->response;
                continue;
            }

            // Inject route parameters by parameter name
            $name = $parameter->getName();

            if (array_key_exists($name, $params)) {
                $value = $params[$name];

                // Convert route values to their declared scalar types
                if ($typeName === 'int') {
                    $value = (int) $value;
                } elseif ($typeName === 'float') {
                    $value = (float) $value;
                } elseif ($typeName === 'string') {
                    $value = (string) $value;
                } elseif ($typeName === 'bool') {
                    $value = filter_var(
                        $value,
                        FILTER_VALIDATE_BOOLEAN
                    );
                }

                $arguments[] = $value;
                continue;
            }

            // Use a default value if one is available
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Cannot resolve parameter \${$name} for "
                . get_class($controller)
                . "::{$methodName}()"
            );
        }

        return $reflection->invokeArgs($controller, $arguments);
    }

    private function matchPath(string $routePath, string $requestPath): ?array
    {
        $routeSegments = explode('/', trim($routePath, '/'));
        $requestSegments = explode('/', trim($requestPath, '/'));

        if (count($routeSegments) !== count($requestSegments)) {
            return null;
        }

        $params = [];

        foreach ($routeSegments as $index => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $paramName = trim($segment, '{}');
                $params[$paramName] = $requestSegments[$index];
            } elseif ($segment !== $requestSegments[$index]) {
                return null;
            }
        }

        return $params;
    }
}