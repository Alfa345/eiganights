<?php
// app/Core/Router.php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $params = [];

    public function add($route, $params = []) {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        $route = preg_replace('/\{([a-z_]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    public function match($url) {
        // The $url passed here from dispatch() is already trimmed and query string removed.
        // It's also relative to the application's base.
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    public function dispatch($url) {
        // $url is already processed by public/index.php to be the path relative to app root
        if ($this->match($url)) {
            $controllerName = $this->params['controller'];
            $controllerName = $this->convertToStudlyCaps($controllerName) . 'Controller';
            $controllerClass = "App\\Controllers\\$controllerName";

            if (class_exists($controllerClass)) {
                $controller_object = new $controllerClass($this->params);
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);

                if (method_exists($controller_object, $action)) {
                    call_user_func_array([$controller_object, $action], $this->extractActionParameters($controller_object, $action));
                } else {
                    throw new \Exception("Action '$action' (in controller '$controllerClass') not found for URL '$url'", 404);
                }
            } else {
                throw new \Exception("Controller class '$controllerClass' not found for URL '$url'", 404);
            }
        } else {
            throw new \Exception('No route matched for URL: ' . htmlspecialchars($url), 404);
        }
    }
    
    private function extractActionParameters($controllerObject, $actionMethod) {
        $params = [];
        try {
            $reflectionMethod = new \ReflectionMethod($controllerObject, $actionMethod);
            foreach ($reflectionMethod->getParameters() as $param) {
                $paramName = $param->getName();
                if (isset($this->params[$paramName])) {
                    $params[] = $this->params[$paramName];
                } elseif ($param->isDefaultValueAvailable()) {
                    $params[] = $param->getDefaultValue();
                } else {
                    // If a required parameter is not available, it might lead to an error when calling the action.
                    // Depending on strictness, you could throw an error here.
                    // For now, let PHP handle it if a required param is missing.
                     $params[] = null; // Or some other default / error
                }
            }
        } catch (\ReflectionException $e) {
            // Handle reflection error, e.g. method not found (though method_exists should catch this)
            error_log("ReflectionException in extractActionParameters for {$actionMethod}: " . $e->getMessage());
        }
        return $params;
    }

    protected function convertToStudlyCaps($string) {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    protected function convertToCamelCase($string) {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    public function getParams() {
        return $this->params;
    }
}