<?php
// Variables like $pageTitle (from controller), $siteName (from config or controller) are expected.
$siteNameForDisplay = htmlspecialchars($siteName ?? (defined('SITE_NAME') ? SITE_NAME : 'EigaNights'), ENT_QUOTES, 'UTF-8');
$effectivePageTitle = htmlspecialchars($pageTitle ?? $siteNameForDisplay, ENT_QUOTES, 'UTF-8');
$logoPath = BASE_URL . 'assets/images/eiganights_logov2.png';
$headerSearchQuery = htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8');

// Determine active link - simplified for brevity
$currentPath = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
$basePathTrimmed = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
if (!empty($basePathTrimmed) && strpos($currentPath, $basePathTrimmed) === 0) {
    $currentRelativePath = trim(substr($currentPath, strlen($basePathTrimmed)), '/');
} else {
    $currentRelativePath = $currentPath;
}
if ($currentRelativePath === 'index.php') $currentRelativePath = '';


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Eiganights - Découvrez, notez et discutez de films avec une communauté de passionnés.">
    <title><?php echo $effectivePageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/style.css'; ?>" />
    <?php if (isset($RECAPTCHA_SITE_KEY_V3) && !empty($RECAPTCHA_SITE_KEY_V3) && (strpos($currentRelativePath, 'login') !== false )) : /* Only for login page for V3 */ ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($RECAPTCHA_SITE_KEY_V3, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php elseif (isset($RECAPTCHA_SITE_KEY_V2) && !empty($RECAPTCHA_SITE_KEY_V2) && (strpos($currentRelativePath, 'register') !== false)) : /* Only for register page for V2 */ ?>
         <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>
<header class="site-header">
    <div class="header-container container">
        <div class="site-branding">
             <a href="<?php echo BASE_URL; ?>" class="site-logo-link" aria-label="Page d'accueil <?php echo $siteNameForDisplay; ?>">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo <?php echo $siteNameForDisplay; ?>" class="site-logo-image">
                <span class="site-title-header"><?php echo $siteNameForDisplay; ?></span>
             </a>
        </div>
        <nav class="main-navigation" aria-label="Navigation principale">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo ($currentRelativePath == '') ? 'active' : ''; ?>">Accueil</a></li>
                <li><a href="<?php echo BASE_URL . 'forum'; ?>" class="nav-link <?php echo (strpos($currentRelativePath, 'forum') === 0) ? 'active' : ''; ?>">Forum</a></li>
                <li><a href="<?php echo BASE_URL . 'users'; ?>" class="nav-link <?php echo ($currentRelativePath == 'users') ? 'active' : ''; ?>">Utilisateurs</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo BASE_URL . 'messages'; ?>" class="nav-link <?php echo (strpos($currentRelativePath, 'messages') === 0) ? 'active' : ''; ?>">Messages</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL . 'admin'; ?>" class="nav-link <?php echo (strpos($currentRelativePath, 'admin') === 0) ? 'active' : ''; ?>">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL . 'profile'; ?>" class="nav-link <?php echo ($currentRelativePath == 'profile') ? 'active' : ''; ?>">Mon Profil</a></li>
                    <li><a href="<?php echo BASE_URL . 'logout'; ?>" class="nav-link">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL . 'login'; ?>" class="nav-link <?php echo ($currentRelativePath == 'login') ? 'active' : ''; ?>">Connexion</a></li>
                    <li><a href="<?php echo BASE_URL . 'register'; ?>" class="nav-link <?php echo ($currentRelativePath == 'register') ? 'active' : ''; ?>">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
         <div class="header-search-bar">
            <form method="GET" action="<?php echo BASE_URL; ?>" class="search-form-header" role="search">
                <label for="header-search-input" class="visually-hidden">Rechercher un film</label>
                <input type="text" id="header-search-input" name="search" placeholder="Rechercher un film..." value="<?php echo $headerSearchQuery; ?>" aria-label="Champ de recherche de film" />
                <button type="submit" class="search-button-header" aria-label="Lancer la recherche">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</header>
<div class="container page-content">