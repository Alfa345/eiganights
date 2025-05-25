<?php
// public/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dans public/index.php
require_once __DIR__ . '/../vendor/autoload.php';
// La config globale est chargée ici, ce qui rendra $conn disponible si Model le requiert globalement.
require_once __DIR__ . '/../app/config/config.php';

// Les fonctions de app/Helpers/functions.php sont maintenant autoloadées si transformées en classe,
// ou incluses manuellement si elles restent des fonctions procédurales.
// Pour la simplicité, nous gardons l'inclusion manuelle ici si c'est encore un fichier de fonctions.
if (file_exists(__DIR__ . '/../app/Helpers/functions.php')) {
    require_once __DIR__ . '/../app/Helpers/functions.php';
}


$router = new App\Core\Router();

// --- Définition des Routes ---
// Static Pages
$router->add('', ['controller' => 'Home', 'action' => 'index']); // Page d'accueil
$router->add('faq', ['controller' => 'StaticPage', 'action' => 'faq']);
$router->add('terms', ['controller' => 'StaticPage', 'action' => 'terms']);
$router->add('contact', ['controller' => 'StaticPage', 'action' => 'contact']); // Logique de contact à déplacer

// Auth
$router->add('login', ['controller' => 'Auth', 'action' => 'login']);
$router->add('register', ['controller' => 'Auth', 'action' => 'register']);
$router->add('logout', ['controller' => 'Auth', 'action' => 'logout']);
$router->add('forgot-password', ['controller' => 'Auth', 'action' => 'forgotPassword']);
$router->add('reset-password', ['controller' => 'Auth', 'action' => 'resetPassword']); // Aura besoin du token en paramètre

// Movies
$router->add('movie/details/{id:\d+}', ['controller' => 'Movie', 'action' => 'details']);
$router->add('movie/add-to-watchlist', ['controller' => 'Movie', 'action' => 'addToWatchlist']); // Pour add.php
$router->add('movie/remove-from-watchlist', ['controller' => 'Movie', 'action' => 'removeFromWatchlist']); // Pour remove_from_watchlist.php
$router->add('movie/rate-comment', ['controller' => 'Movie', 'action' => 'rateOrComment']); // Pour rate_comment.php

// Profile & Users
$router->add('profile', ['controller' => 'Profile', 'action' => 'myProfile']);
$router->add('profile/view/{id:\d+}', ['controller' => 'Profile', 'action' => 'viewProfile']);
$router->add('profile/viewbyusername/{username:[a-zA-Z0-9_]+}', ['controller' => 'Profile', 'action' => 'viewProfileByUsername']);
$router->add('users', ['controller' => 'User', 'action' => 'listUsers']); // Pour users_list.php
$router->add('friend-action', ['controller' => 'Friend', 'action' => 'handleAction']); // Pour friend_action.php

// Forum
$router->add('forum', ['controller' => 'Forum', 'action' => 'index']);
$router->add('forum/thread/{id:\d+}', ['controller' => 'Forum', 'action' => 'viewThread']);
$router->add('forum/create-thread', ['controller' => 'Forum', 'action' => 'createThread']);
$router->add('forum/edit-thread/{id:\d+}', ['controller' => 'Forum', 'action' => 'editThread']);
$router->add('forum/delete-thread', ['controller' => 'Forum', 'action' => 'deleteThread']); // Pour forum_delete_thread.php
// Les posts/réponses seront gérés dans ForumController->viewThread (pour la création) ou ForumPostController

// Messaging
$router->add('messages', ['controller' => 'Message', 'action' => 'index']);
$router->add('messages/new', ['controller' => 'Message', 'action' => 'startConversation']);
$router->add('messages/conversation/{id:\d+}', ['controller' => 'Message', 'action' => 'viewConversation']);
$router->add('messages/send', ['controller' => 'Message', 'action' => 'sendMessage']); // Action pour POSTer un message

// Admin
$router->add('admin', ['controller' => 'Admin', 'action' => 'dashboard']);
$router->add('admin/users', ['controller' => 'AdminUser', 'action' => 'manageUsers']); // Si on sépare
$router->add('admin/user-action', ['controller' => 'Admin', 'action' => 'userAction']); // Pour admin_action.php
$router->add('admin/content', ['controller' => 'AdminContent', 'action' => 'listContent']); //
$router->add('admin/content/edit/{slug:[a-zA-Z0-9-]+}', ['controller' => 'AdminContent', 'action' => 'editDbContent']);
$router->add('admin/faq/edit/{id:\d+}', ['controller' => 'AdminContent', 'action' => 'editFaqItem']);
$router->add('admin/faq/add', ['controller' => 'AdminContent', 'action' => 'addFaqItem']);
$router->add('admin/faq/delete/{id:\d+}', ['controller' => 'AdminContent', 'action' => 'deleteFaqItem']);
$router->add('admin/manage-file-terms', ['controller' => 'AdminContent', 'action' => 'manageFileBasedTerms']);


// API Proxy (si vous le gardez, il peut être un endpoint spécial ou un controller)
// Pour l'instant, il peut rester un fichier à part si votre .htaccess le permet
// Ou vous pouvez créer une route simple
$router->add('api/tmdb-proxy', ['controller' => 'ApiProxy', 'action' => 'tmdbSearch']);
// Dans public/index.php

// ... (includes et instanciation du routeur) ...

// $router->add(... routes ...)

// Dans public/index.php
try {
    $requestUri = $_SERVER['REQUEST_URI'];

    // Chemin de base du projet DANS le DocumentRoot du serveur.
    // Pour http://localhost/eiganights/faq, $projectBasePath devrait être /eiganights
    // C'est ce qu'on veut retirer pour obtenir "faq"
    $projectBasePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // SCRIPT_NAME est /eiganights/public/index.php
    $projectBasePath = str_replace('/public', '', $projectBasePath); // Devient /eiganights

    $routeToMatch = $requestUri;
    if ($projectBasePath !== '' && strpos($requestUri, $projectBasePath) === 0) {
        $routeToMatch = substr($requestUri, strlen($projectBasePath));
    }

    $routeToMatch = trim(strtok($routeToMatch, '?'), '/');
    if ($routeToMatch === false) {
        $routeToMatch = '';
    }

    $router->dispatch($routeToMatch);

} catch (\Exception $e) {
    // ... gestionnaire d'erreur ...
}


try {
    // REQUEST_URI contient la query string. Le routeur doit pouvoir la séparer.
    $router->dispatch($_SERVER['REQUEST_URI']);
} catch (\Exception $e) {
    $errorCode = $e->getCode() === 404 ? 404 : 500;
    if (!headers_sent()) {
        http_response_code($errorCode);
    }
    // Tenter de charger le header et footer pour une page d'erreur plus jolie
    // Vous aurez besoin d'une vue d'erreur générique.
    $pageTitle = "Erreur " . $errorCode;
    if (class_exists('\App\Core\Controller')) { // Vérifie si la classe de base existe
        // Créer une instance anonyme simple pour utiliser renderLayout
        $errorDisplayController = new class([]) extends \App\Core\Controller {
            public function showErrorPage($code, $message, $pageTitleForLayout) {
                 // Vous devez créer app/Views/error/error.php
                 $this->renderLayout('error/error.php', [
                     'pageTitle' => $pageTitleForLayout,
                     'errorCode' => $code,
                     'errorMessage' => $message
                 ]);
            }
        };
        // Pourrait ne pas fonctionner si BASE_URL ou config ne sont pas encore totalement settées
        // Dans ce cas, un simple echo est plus sûr pour l'erreur critique.
        try {
             if (file_exists(__DIR__ . '/../app/Views/error/error.php') && defined('BASE_URL')) {
                $errorDisplayController->showErrorPage($errorCode, $e->getMessage(), $pageTitle);
             } else {
                echo "<h1>Erreur {$errorCode}</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
             }
        } catch (\Throwable $renderError) { // Attrape les erreurs de rendu
            echo "<h1>Erreur Critique {$errorCode}</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>Erreur supplémentaire lors du rendu de la page d'erreur: " . htmlspecialchars($renderError->getMessage()) . "</p>";
        }
    } else {
         echo "<h1>Erreur {$errorCode}</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }

    error_log("EigaNights Exception: Code[{$e->getCode()}] " . $e->getMessage() . "\n" . $e->getTraceAsString());
}