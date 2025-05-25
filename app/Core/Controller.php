<?php
// app/Core/Controller.php
namespace App\Core;

abstract class Controller {
    protected $route_params = [];

    public function __construct($route_params) {
        $this->route_params = $route_params;
    }

    /**
     * Méthode magique appelée lorsqu'une méthode inexistante est appelée sur un objet de cette classe.
     * Utilisée pour appeler une méthode d'action avec un suffixe '_before' et '_after' si elles existent.
     * @param string $name  Nom de la méthode.
     * @param array $args   Arguments passés à la méthode.
     * @throws \Exception
     */
    public function __call($name, $args) {
        $method = $name . 'Action'; // Ancienne convention, peut-être à revoir
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    /**
     * Filtre 'Before' - exécuté avant les méthodes d'action.
     * Peut être surchargé dans les contrôleurs enfants.
     */
    protected function before() {
        // Exemple: vérifier l'authentification pour certaines actions
        return true; // Retourner false pour arrêter l'exécution de l'action
    }

    /**
     * Filtre 'After' - exécuté après les méthodes d'action.
     * Peut être surchargé dans les contrôleurs enfants.
     */
    protected function after() {
    }

    /**
     * Rend une vue.
     * @param string $view Le fichier de vue (ex: 'Home/index.php').
     * @param array $args  Arguments à extraire en variables pour la vue.
     * @throws \Exception si le fichier de vue n'est pas trouvé.
     */
    protected function render($view, $args = []) {
        extract($args);

        $file = __DIR__ . "/../Views/$view";

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("View file '$file' not found");
        }
    }
    
    /**
     * Rend une vue avec un layout (header et footer).
     * @param string $view Le fichier de vue principal.
     * @param array $args  Arguments à passer à la vue et aux layouts.
     */
    protected function renderLayout($view, $args = []) {
        // Pour rendre $pageTitle accessible dans header.php
        // et potentiellement d'autres variables globales pour le layout.
        // extract($args); // Les variables sont déjà extraites si $args est passé à render.

        ob_start();
        $this->render("layouts/header.php", $args);
        $this->render($view, $args);
        $this->render("layouts/footer.php", $args);
        echo ob_get_clean();
    }

    /**
     * Redirige vers une autre URL.
     * @param string $url L'URL de destination.
     */
    protected function redirect($url) {
        // Si l'URL ne commence pas par http, assume que c'est une route interne.
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * Requiert que l'utilisateur soit connecté. Redirige vers la page de connexion sinon.
     * @param string $redirectUrlAfterLogin URL vers laquelle rediriger après la connexion (optionnel).
     */
    protected function requireLogin($redirectUrlAfterLogin = '') {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['login_required_message'] = "Vous devez être connecté pour accéder à cette page.";
            $redirectTo = 'login';
            if (!empty($redirectUrlAfterLogin)) {
                $redirectTo .= '?redirect=' . urlencode($redirectUrlAfterLogin);
            } else {
                // Essayer de rediriger vers la page actuelle après connexion
                $currentRelativeUrl = ltrim(str_replace(rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'), '', $_SERVER['REQUEST_URI']), '/');
                $redirectTo .= '?redirect=' . urlencode($currentRelativeUrl);
            }
            $this->redirect($redirectTo);
        }
    }

    /**
     * Requiert que l'utilisateur ait le rôle d'admin.
     */
    protected function requireAdmin() {
        $this->requireLogin(); // Un admin doit d'abord être connecté
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Accès non autorisé. Droits administrateur requis.";
            // Rediriger vers une page appropriée, ex: la page d'accueil ou le profil utilisateur
            $this->redirect(''); // Page d'accueil
        }
    }
}