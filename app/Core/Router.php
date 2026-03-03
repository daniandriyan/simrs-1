<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router Class
 * 
 * Handles URL routing with support for static and dynamic routes.
 * Supports pattern matching with :any and :int placeholders.
 */
class Router
{
    /**
     * @var array<string, callable|array> Registered routes
     */
    private array $routes = [];

    /**
     * @var array<string, string> Route patterns for placeholders
     */
    private array $patterns = [
        ':any' => '[^/]+',
        ':int' => '[0-9]+',
        ':str' => '[a-zA-Z0-9_-]+',
        ':slug' => '[a-z0-9-]+',
        ':uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ];

    /**
     * @var array<string> Route groups
     */
    private array $groups = [];

    /**
     * @var array<string> Middleware stack
     */
    private array $middleware = [];

    /**
     * Register a GET route
     *
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    public function get(string $pattern, callable|array $callback): self
    {
        return $this->addRoute('GET', $pattern, $callback);
    }

    /**
     * Register a POST route
     *
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    public function post(string $pattern, callable|array $callback): self
    {
        return $this->addRoute('POST', $pattern, $callback);
    }

    /**
     * Register a PUT route
     *
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    public function put(string $pattern, callable|array $callback): self
    {
        return $this->addRoute('PUT', $pattern, $callback);
    }

    /**
     * Register a DELETE route
     *
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    public function delete(string $pattern, callable|array $callback): self
    {
        return $this->addRoute('DELETE', $pattern, $callback);
    }

    /**
     * Register a route for any HTTP method
     *
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    public function any(string $pattern, callable|array $callback): self
    {
        return $this->addRoute('*', $pattern, $callback);
    }

    /**
     * Register a route group with shared prefix
     *
     * @param string $prefix Group prefix
     * @param callable $callback Group routes definition
     * @return self
     */
    public function group(string $prefix, callable $callback): self
    {
        $this->groups[] = $prefix;
        $callback($this);
        array_pop($this->groups);
        return $this;
    }

    /**
     * Add middleware to routes
     *
     * @param string $middleware Middleware class name
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add a route to the registry
     *
     * @param string $method HTTP method
     * @param string $pattern URL pattern
     * @param callable|array $callback Route handler
     * @return self
     */
    private function addRoute(string $method, string $pattern, callable|array $callback): self
    {
        $prefix = implode('', $this->groups);
        $fullPattern = rtrim($prefix, '/') . '/' . ltrim($pattern, '/');
        
        if ($fullPattern === '/') {
            $fullPattern = '/';
        } else {
            $fullPattern = '/' . trim($fullPattern, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'callback' => $callback,
            'middleware' => $this->middleware,
        ];

        return $this;
    }

    /**
     * Execute the router and dispatch the matched route
     *
     * @return mixed Response from the route handler
     */
    public function dispatch(): mixed
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle base path if application is in subdirectory
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptName !== '/') {
            $requestUri = substr($requestUri, strlen($scriptName));
        }
        $requestUri = '/' . trim($requestUri, '/');
        if ($requestUri === '/') {
            $requestUri = '';
        }

        // Sort routes: specific routes first, then parameterized routes
        usort($this->routes, function ($a, $b) {
            $aParams = substr_count($a['pattern'], ':');
            $bParams = substr_count($b['pattern'], ':');
            $aSegments = substr_count($a['pattern'], '/');
            $bSegments = substr_count($b['pattern'], '/');
            
            if ($aParams === $bParams) {
                return $bSegments <=> $aSegments;
            }
            return $aParams <=> $bParams;
        });

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->compilePattern($route['pattern']);
            
            if (preg_match('#^' . $pattern . '$#i', $requestUri, $matches)) {
                // Extract parameters from matches
                array_shift($matches);
                $params = array_filter($matches, fn($v) => $v !== '');
                $params = array_values($params);

                // Execute middleware
                foreach ($route['middleware'] as $middleware) {
                    $result = $this->executeMiddleware($middleware, $params);
                    if ($result === false) {
                        return null;
                    }
                }

                // Execute route callback
                return $this->executeCallback($route['callback'], $params);
            }
        }

        // No route matched - 404
        return $this->handleNotFound();
    }

    /**
     * Compile route pattern by replacing placeholders with regex
     *
     * @param string $pattern Route pattern
     * @return string Compiled regex pattern
     */
    private function compilePattern(string $pattern): string
    {
        // Replace named parameters
        $compiled = preg_replace_callback('/:(\w+)/', function ($matches) {
            $placeholder = $matches[0];
            return $this->patterns[$placeholder] ?? $this->patterns[':any'];
        }, $pattern);

        return $compiled;
    }

    /**
     * Execute route callback
     *
     * @param callable|array $callback Route handler
     * @param array $params Route parameters
     * @return mixed Response from callback
     */
    private function executeCallback(callable|array $callback, array $params): mixed
    {
        if (is_array($callback)) {
            // Controller@method format
            [$controller, $method] = $callback;
            
            if (!class_exists($controller)) {
                throw new \RuntimeException("Controller not found: {$controller}");
            }

            $instance = new $controller();
            
            if (!method_exists($instance, $method)) {
                throw new \RuntimeException("Method not found: {$controller}::{$method}");
            }

            return call_user_func_array([$instance, $method], $params);
        }

        return call_user_func_array($callback, $params);
    }

    /**
     * Execute middleware
     *
     * @param string $middleware Middleware class name
     * @param array $params Route parameters
     * @return bool Continue execution or stop
     */
    private function executeMiddleware(string $middleware, array $params): bool
    {
        if (!class_exists($middleware)) {
            throw new \RuntimeException("Middleware not found: {$middleware}");
        }

        $instance = new $middleware();
        
        if (!method_exists($instance, 'handle')) {
            throw new \RuntimeException("Middleware must have a handle method: {$middleware}");
        }

        $result = call_user_func_array([$instance, 'handle'], $params);
        
        return $result !== false;
    }

    /**
     * Handle 404 Not Found
     *
     * @return string 404 response
     */
    private function handleNotFound(): string
    {
        http_response_code(404);
        
        // Check if there's a custom 404 handler
        $errorView = views_path('errors/404.html');
        if (file_exists($errorView)) {
            http_response_code(404);
            return file_get_contents($errorView);
        }

        return '<h1>404 - Page Not Found</h1><p>The requested page could not be found.</p>';
    }

    /**
     * Get all registered routes
     *
     * @return array Registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Generate URL for a named route (future implementation)
     *
     * @param string $name Route name
     * @param array $params Route parameters
     * @return string Generated URL
     */
    public function route(string $name, array $params = []): string
    {
        // Named routes can be implemented in future versions
        return '';
    }
}
