<?php
// app/Core/Router.php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $params = [];

    /**
     * Ajoute une route à la table de routage.
     * @param string $route  L'URL de la route (ex: 'posts/show/{id:\d+}')
     * @param array  $params Paramètres (controller, action, etc.)
     */
    public function add($route, $params = []) {
        // Convertit la route en une expression régulière : échappe les slashes
        $route = preg_replace('/\//', '\\/', $route);
        // Convertit les variables (ex: {controller})
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);
        // Convertit les variables avec des expressions régulières personnalisées (ex: {id:\d+})
        $route = preg_replace('/\{([a-z_]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        // Ajoute les délimiteurs de début et de fin, et l'insensibilité à la casse
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    /**
     * Tente de matcher l'URL avec les routes de la table.
     * Si une route est trouvée, les paramètres sont stockés.
     * @param string $url L'URL à matcher.
     * @return boolean true si une route est trouvée, false sinon.
     */
    public function match($url) {
        // Nettoyer l'URL de base si le projet est dans un sous-dossier
        $basePath = '';
        if (defined('BASE_URL')) {
            $urlParts = parse_url(BASE_URL);
            if (isset($urlParts['path']) && $urlParts['path'] !== '/') {
                $basePath = trim($urlParts['path'], '/');
                // Si l'URL commence par le basePath, le retirer pour le matching
                if ($basePath !== '' && strpos($url, $basePath) === 0) {
                    $url = trim(substr($url, strlen($basePath)), '/');
                }
            }
        }
        $url = trim($url, '/'); // Nettoyer aussi le slash initial/final de l'URL traitée


        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                $this->params = $params;
                foreach ($matches as $key => $match) {
                if (is_string($key)) {
                    $this->params[$key] = $match;
                }
            }
                return true;
                
            }
        }
        return false;
    }

    /**
     * Dispatche la route, créant le contrôleur et exécutant l'action.
     * @param string $url L'URL demandée.
     * @throws \Exception si la classe Controller ou la méthode Action n'est pas trouvée.
     */
    public function dispatch($url) {
        $original_url_for_matching = $url; // Pour la fonction match
        $url = trim(strtok($url, '?'), '/'); // Garde la query string pour $_GET mais la retire pour le matching

        if ($this->match($original_url_for_matching)) {
            $controllerName = $this->params['controller'];
            $controllerName = $this->convertToStudlyCaps($controllerName) . 'Controller';
            $controllerClass = "App\\Controllers\\$controllerName";

            if (class_exists($controllerClass)) {
                $controller_object = new $controllerClass($this->params);
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);

                if (method_exists($controller_object, $action)) {
                    // Passer les paramètres de route (ex: {id}) à l'action si l'action les attend
                    // Cela nécessite une réflexion ou une convention sur la signature des méthodes d'action
                    // Pour la simplicité, on peut les passer via le constructeur, ou les récupérer de $this->params
                    call_user_func_array([$controller_object, $action], $this->extractActionParameters($controller_object, $action));
                } else {
                    throw new \Exception("Action '$action' (in controller '$controllerClass') not found", 404);
                }
            } else {
                throw new \Exception("Controller class '$controllerClass' not found", 404);
            }
        } else {
            throw new \Exception('No route matched for URL: ' . htmlspecialchars($original_url_for_matching), 404);
        }
    }
    
    /**
     * Extrait les paramètres de l'action à partir des paramètres de la route.
     * Simple implémentation : si l'action attend 'id' et que 'id' est dans $this->params.
     */
    private function extractActionParameters($controllerObject, $actionMethod) {
        $params = [];
        $reflectionMethod = new \ReflectionMethod($controllerObject, $actionMethod);
        foreach ($reflectionMethod->getParameters() as $param) {
            if (isset($this->params[$param->getName()])) {
                $params[] = $this->params[$param->getName()];
            } elseif ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } else {
                // Pourrait lancer une exception si un paramètre requis n'est pas dans la route
                // ou juste passer null si le type le permet.
                // Pour la simplicité, on assume que les params de route couvrent les besoins
                // ou que les actions utilisent $this->route_params directement.
            }
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