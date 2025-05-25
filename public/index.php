<?php
// public/index.php

// This should be one ofr the very first things, to catch any startup errors
// However, if config.php has a parse error itself, this won't catch it.
// Consider having a separate, minimal error handler include before anything else if 502s persist.
ini_set('display_errors', 1); // Set to 0 in production if Heroku panel doesn't override
ini_set('display_startup_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/config.php'; // $conn and BASE_URL become available

if (file_exists(__DIR__ . '/../app/Helpers/functions.php')) {
    require_once __DIR__ . '/../app/Helpers/functions.php';
}

$router = new App\Core\Router();

// --- Route Definitions ---
// Static Pages
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('faq', ['controller' => 'StaticPage', 'action' => 'faq']);
$router->add('terms', ['controller' => 'StaticPage', 'action' => 'terms']);
$router->add('contact', ['controller' => 'StaticPage', 'action' => 'contact']);

// Auth Routes (will require AuthController.php)
$router->add('login', ['controller' => 'Auth', 'action' => 'login']);
$router->add('register', ['controller' => 'Auth', 'action' => 'register']);
$router->add('logout', ['controller' => 'Auth', 'action' => 'logout']); // Will redirect to a root script for now
$router->add('forgot-password', ['controller' => 'Auth', 'action' => 'forgotPassword']);
$router->add('reset-password', ['controller' => 'Auth', 'action' => 'resetPassword']);

// Movie Routes (will require MovieController.php)
$router->add('movie/details/{id:\d+}', ['controller' => 'Movie', 'action' => 'details']);
// For actions like add/remove watchlist, rate/comment, these point to root scripts
// The router could be updated to point to controller actions if these are refactored.
// For now, let them be handled by .htaccess/Nginx serving those PHP files directly.

// Profile & Users (will require ProfileController.php, UserController.php)
$router->add('profile', ['controller' => 'Profile', 'action' => 'myProfile']);
$router->add('profile/view/{id:\d+}', ['controller' => 'Profile', 'action' => 'viewProfileById']);
$router->add('profile/viewbyusername/{username:[a-zA-Z0-9_]+}', ['controller' => 'Profile', 'action' => 'viewProfileByUsername']);
$router->add('users', ['controller' => 'User', 'action' => 'listAll']);

// Forum (will require ForumController.php)
$router->add('forum', ['controller' => 'Forum', 'action' => 'index']);
$router->add('forum/thread/{id:\d+}', ['controller' => 'Forum', 'action' => 'viewThread']);
$router->add('forum/create-thread', ['controller' => 'Forum', 'action' => 'createThread']);
$router->add('forum/edit-thread/{id:\d+}', ['controller' => 'Forum', 'action' => 'editThread']);
// forum_delete_thread.php is a root script

// Messaging (will require MessageController.php)
$router->add('messages', ['controller' => 'Message', 'action' => 'index']);
$router->add('messages/new', ['controller' => 'Message', 'action' => 'startConversation']); // For message_start_conversation.php
$router->add('messages/conversation/{id:\d+}', ['controller' => 'Message', 'action' => 'viewConversation']);
// messages/send will be a POST action to viewConversation or its own action.

// Admin (will require AdminController.php and potentially sub-controllers)
$router->add('admin', ['controller' => 'Admin', 'action' => 'dashboard']);
$router->add('admin/edit-faq', ['controller' => 'Admin', 'action' => 'editFaq']); // Manages add/edit/delete FAQ
$router->add('admin/edit-content', ['controller' => 'Admin', 'action' => 'editSiteContent']); // Manages DB content
$router->add('admin/manage-terms', ['controller' => 'Admin', 'action' => 'manageFileTerms']); // Manages file content
// admin_action.php is a root script

// API Proxy (can be a controller or remain standalone)
// $router->add('api/tmdb-proxy', ['controller' => 'ApiProxy', 'action' => 'tmdbSearch']);
// For now, api_tmdb_proxy.php will be accessed directly if Nginx allows.

try {
    $url = $_SERVER['REQUEST_URI'];

    // Remove the base path of the application if it's installed in a subdirectory
    // and the DocumentRoot is not pointing directly to /public
    // However, if Herogu DocumentRoot is /public, SCRIPT_NAME might be /index.php
    // and BASE_URL would be "https://eiganights.herogu.garageisep.com/"
    // In this case, the path to match is just $url after the domain.

    $baseDir = str_replace('/public', '', dirname($_SERVER['SCRIPT_NAME']));
    $requestPath = '/'; // Default to root
    if (strlen($baseDir) > 1 && strpos($url, $baseDir) === 0) { // if $baseDir is not just '/'
        $requestPath = substr($url, strlen($baseDir));
    } else {
        $requestPath = $url;
    }
    
    $routeToMatch = trim(strtok($requestPath, '?'), '/');
    if ($routeToMatch === false || $routeToMatch === 'index.php') { // Also handle direct index.php access
        $routeToMatch = ''; // Match the root route
    }
    
    $router->dispatch($routeToMatch);

} catch (\Exception $e) {
    $errorCode = $e->getCode();
    if ($errorCode !== 404 && $errorCode !== 403) { // For other errors, set 500
        $errorCode = 500;
    }
    if (!headers_sent()) {
        http_response_code($errorCode);
    }

    $pageTitle = "Erreur " . $errorCode;
    $errorMessage = $e->getMessage();
    
    // Log the full error for server-side debugging
    error_log("EigaNights Exception: Code[{$e->getCode()}] {$errorMessage}\nURL: {$_SERVER['REQUEST_URI']}\nTrace: {$e->getTraceAsString()}");

    // Attempt to render a user-friendly error page using the layout
    if (class_exists('\App\Core\Controller') && is_readable(__DIR__ . '/../app/Views/error/error.php') && defined('BASE_URL')) {
        $errorDisplayController = new class([]) extends \App\Core\Controller {
            public function showErrorPage($code, $message, $pageTitleForLayout) {
                $this->renderLayout('error/error.php', [
                    'pageTitle' => $pageTitleForLayout,
                    'errorCode' => $code,
                    'errorMessage' => ($code == 500 && !(ini_get('display_errors') === '1' || ini_get('display_errors') === true)) ? "Une erreur interne est survenue. Veuillez réessayer plus tard." : $message
                ]);
            }
        };
        try {
            $errorDisplayController->showErrorPage($errorCode, $errorMessage, $pageTitle);
        } catch (\Throwable $renderError) {
            error_log("Critical Error: Failed to render error page. {$renderError->getMessage()}");
            echo "<h1>Erreur {$errorCode}</h1><p>Une erreur critique est survenue. Détails: " . htmlspecialchars($errorMessage) . "</p>";
            echo "<p>Erreur supplémentaire lors du rendu de la page d'erreur: " . htmlspecialchars($renderError->getMessage()) . "</p>";
        }
    } else {
        // Fallback to simple error message if controller or view is not available
        echo "<h1>Erreur {$errorCode}</h1><p>" . htmlspecialchars($errorMessage) . "</p>";
        if(ini_get('display_errors') === '1' || ini_get('display_errors') === true) {
             echo "<pre>{$e->getTraceAsString()}</pre>"; // Show trace only if display_errors is on
        }
    }
}