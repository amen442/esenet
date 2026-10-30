<?php

namespace App\Core;

class Router
{
    private static $instance = null;
    private $routes = [];
    private $basePath = '';

    public function __construct($basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addRoute($method, $path, $controller, $action)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path from request path
        if ($this->basePath && strpos($requestPath, $this->basePath) === 0) {
            $requestPath = substr($requestPath, strlen($this->basePath));
        }
        
        // Remove query string
        $requestPath = strtok($requestPath, '?');
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $this->executeController($route['controller'], $route['action']);
                return;
            }
        }
        
        // 404 - Route not found
        http_response_code(404);
        $this->show404();
    }

    private function matchPath($routePath, $requestPath)
    {
        // Convert route path to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestPath, $matches)) {
            // Store parameters in $_GET for easy access
            array_shift($matches); // Remove full match
            $paramNames = [];
            preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
            
            foreach ($paramNames[1] as $index => $name) {
                $_GET[$name] = $matches[$index] ?? null;
            }
            
            return true;
        }
        
        return false;
    }

    private function executeController($controllerName, $action)
    {
        $controllerClass = "App\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $action)) {
            throw new \Exception("Method {$action} not found in {$controllerClass}");
        }
        
        $controller->$action();
    }

    private function show404()
    {
        include __DIR__ . '/../Views/errors/404.php';
    }

    public function generateUrl($routeName, $params = [])
    {
        foreach ($this->routes as $route) {
            if ($route['path'] === $routeName) {
                $url = $this->basePath . $route['path'];
                
                foreach ($params as $key => $value) {
                    $url = str_replace('{' . $key . '}', $value, $url);
                }
                
                return $url;
            }
        }
        
        return '#';
    }
}
