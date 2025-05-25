<?php // logout.php
require_once __DIR__ . '/app/config/config.php'; // Session is started in config

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to the MVC home route
header('Location: ' . BASE_URL);
exit;