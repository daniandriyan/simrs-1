<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, $callback): void
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post(string $path, $callback): void
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            foreach ($this->routes[$method] as $routePath => $routeCallback) {
                $pattern = preg_replace('/:[a-zA-Z0-9]+/', '([a-zA-Z0-9_-]+)', $routePath);
                $pattern = "@^" . $pattern . "$@";
                
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches);
                    return $this->executeCallback($routeCallback, $matches);
                }
            }
            $this->response->setStatusCode(404);
            return "404 Not Found";
        }

        return $this->executeCallback($callback);
    }

    private function executeCallback($callback, array $params = [])
    {
        if (is_array($callback)) {
            $controller = new $callback[0]();
            return call_user_func_array([$controller, $callback[1]], $params);
        }
        return call_user_func_array($callback, $params);
    }
}
