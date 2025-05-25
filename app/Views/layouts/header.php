<?php
// $pageTitle, $siteName, $BASE_URL sont disponibles (passés par le contrôleur ou via config.php)
$logoutText = "Déconnexion";
$headerSearchQuery = $_GET['search'] ?? ''; // Si vous voulez le garder actif ici
$logoPath = BASE_URL . 'assets/images/eiganights_logov2.png';
$siteNameForDisplay = defined('SITE_NAME') ? htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') : 'Eiganights';

$effectivePageTitle = $pageTitle ?? $siteNameForDisplay; // $pageTitle est passé par le controller
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Eiganights - ...">
    <title><?php echo htmlspecialchars($effectivePageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/style.css'; ?>" />
</head>
<body>
<header class="site-header">
    <div class="header-container container">
        <div class="site-branding">
             <a href="<?php echo BASE_URL; ?>" class="site-logo-link">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo <?php echo $siteNameForDisplay; ?>" class="site-logo-image">
                <span class="site-title-header"><?php echo $siteNameForDisplay; ?></span>
             </a>
        </div>
        <nav class="main-navigation" aria-label="Navigation principale">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo (str_replace(rtrim(parse_url(BASE_URL, PHP_URL_PATH),'/'),'',$_SERVER['REQUEST_URI']) == '/' || str_replace(rtrim(parse_url(BASE_URL, PHP_URL_PATH),'/'),'',$_SERVER['REQUEST_URI']) == '') ? 'active' : ''; ?>">Accueil</a></li>
                <li><a href="<?php echo BASE_URL . 'forum'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/forum') !== false ? 'active' : ''; ?>">Forum</a></li>
                <li><a href="<?php echo BASE_URL . 'users'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : ''; ?>">Utilisateurs</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo BASE_URL . 'messages'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/messages') !== false ? 'active' : ''; ?>">Messages</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL . 'admin'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin') !== false ? 'active' : ''; ?>">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL . 'profile'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/profile') !== false && strpos($_SERVER['REQUEST_URI'], '/profile/view') === false ? 'active' : ''; ?>">Mon Profil</a></li>
                    <li><a href="<?php echo BASE_URL . 'logout'; ?>" class="nav-link"><?php echo htmlspecialchars($logoutText); ?></a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL . 'login'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/login') !== false ? 'active' : ''; ?>">Connexion</a></li>
                    <li><a href="<?php echo BASE_URL . 'register'; ?>" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/register') !== false ? 'active' : ''; ?>">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="header-search-bar">
            <form method="GET" action="<?php echo BASE_URL; ?>" class="search-form-header" role="search">
                 <input type="text" id="header-search-input" name="search" placeholder="Rechercher un film..." value="<?php echo htmlspecialchars($headerSearchQuery); ?>" />
                <button type="submit" class="search-button-header" aria-label="Lancer la recherche">...</button>
            </form>
        </div>
    </div>
</header>
<div class="container page-content">