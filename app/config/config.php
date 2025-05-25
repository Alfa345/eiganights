<?php
// app/config/config.php

// ─────────────────────────────────────────────────────────────────────────────
// 0) Site Information
// ─────────────────────────────────────────────────────────────────────────────
define('SITE_NAME', 'EigaNights'); // Définit directement

// ─────────────────────────────────────────────────────────────────────────────
// 1) DEV error reporting
// ─────────────────────────────────────────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─────────────────────────────────────────────────────────────────────────────
// 2) Session setup
// ─────────────────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) Database settings
// ─────────────────────────────────────────────────────────────────────────────
define('DB_HOST', '127.0.0.1');         // Définit directement
define('DB_PORT', 3306);                // Définit directement (ou laissez commenté si c'est optionnel)
define('DB_NAME', 'eiganights');        // Définit directement
define('DB_USER', 'Alfa345');           // Définit directement (Remplacez par votre utilisateur réel)
define('DB_PASS', 'GOON');              // Définit directement (Remplacez par votre mot de passe réel)

// La connexion à la base de données est déplacée APRES la définition des constantes
global $conn;
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, defined('DB_PORT') ? DB_PORT : 3306); // Utilise DB_PORT si défini
$conn = $mysqli;

if ($mysqli->connect_errno) {
    error_log("MySQL connect failed ({$mysqli->connect_errno}): {$mysqli->connect_error}");
    die("Sorry—database temporarily unavailable. Please try again later.");
}
$mysqli->set_charset('utf8mb4');


// ─────────────────────────────────────────────────────────────────────────────
// 4) TMDB API key
// ─────────────────────────────────────────────────────────────────────────────
define('TMDB_API_KEY', '94fc3b99fd623dc63ae00ab80ca1b255'); // Définit directement

// ─────────────────────────────────────────────────────────────────────────────
// 5) Base URL helper
// ─────────────────────────────────────────────────────────────────────────────
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$domain   = $_SERVER['HTTP_HOST'];

// Logique pour déterminer le chemin de base pour BASE_URL
// Assumant que le DocumentRoot pointe vers eiganights/public/ OU que vous êtes dans un sous-dossier
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // SCRIPT_NAME est /public/index.php ou /sous_dossier/public/index.php

// Si SCRIPT_NAME est /index.php (parce que public est le DocumentRoot)
// alors dirname sera / ou .
if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') {
    $baseUrlPath = '';
} else {
    // Si on est dans /eiganights/public/index.php, dirname est /eiganights/public
    // On veut retirer /public pour que BASE_URL soit /eiganights/
    if (substr($scriptDir, -7) === '/public') {
        $baseUrlPath = substr($scriptDir, 0, -7);
    } else {
        $baseUrlPath = $scriptDir;
    }
}
$baseUrlPath = rtrim($baseUrlPath, '/'); // S'assurer qu'il n'y a pas de slash final ici// Dans app/config/config.php
define('BASE_URL', 'http://localhost/eiganights/');


// ─────────────────────────────────────────────────────────────────────────────
// 6) Monetization Settings
// ─────────────────────────────────────────────────────────────────────────────
define('PLACEHOLDER_ADS_ENABLED', true);
define('RANDOM_GIF_ADS_DIRECTORY', 'assets/videos/'); // Relatif à la racine publique
define('DEFAULT_AD_GIF_ALT_TEXT', 'Publicité animée EigaNights');

define('DIRECT_STREAMING_LINKS_ENABLED', true);
define('ALLOWED_API_REGIONS', ['FR', 'US']); // Définit directement
define('STREAMING_PLATFORMS_OFFICIAL_LINKS', [
    8 => ['name' => 'Netflix', 'logo' => 'assets/images/netflix_logo.png', 'search_url_pattern' => 'https://www.netflix.com/search?q={MOVIE_TITLE_URL_ENCODED}'],
    10 => ['name' => 'Amazon Prime Video', 'logo' => 'assets/images/primevideo_logo.png', 'search_url_pattern' => 'https://www.primevideo.com/search/?phrase={MOVIE_TITLE_URL_ENCODED}'],
    337 => ['name' => 'Disney+', 'logo' => 'assets/images/disney_logo.png', 'search_url_pattern' => 'https://www.disneyplus.com/search?q={MOVIE_TITLE_URL_ENCODED}'],
    2 => ['name' => 'Apple TV', 'logo' => 'assets/images/appletv_logo.png', 'search_url_pattern' => 'https://tv.apple.com/search?term={MOVIE_TITLE_URL_ENCODED}'],
]);

// Les constantes ReCAPTCHA et SMTP sont optionnelles ici, elles pourraient être vides si non utilisées
// ou commentées si vous ne les avez pas encore configurées
// define('RECAPTCHA_SITE_KEY_V3', '');
// define('RECAPTCHA_SECRET_KEY_V3', '');
// define('RECAPTCHA_SITE_KEY_V2', '');
// define('RECAPTCHA_SECRET_KEY_V2', '');

// define('SMTP_HOST', '');
// define('SMTP_USERNAME', '');
// define('SMTP_PASSWORD', '');
// define('SMTP_PORT', 587);
// define('SMTP_SECURE', 'tls');
// define('SMTP_FROM_EMAIL', '');
// define('SMTP_FROM_NAME', SITE_NAME);

// La ligne `require_once __DIR__ . '/includes/function.php';` de votre config originale
// a été déplacée ou est gérée par public/index.php via app/Helpers/functions.php
// Si vous avez toujours besoin de ces fonctions *dans* config.php, il faut ajuster le chemin :
// Par exemple, si functions.php est maintenant dans app/Helpers/functions.php:
// if (file_exists(__DIR__ . '/../Helpers/functions.php')) { // Chemin depuis app/config vers app/Helpers
//     require_once __DIR__ . '/../Helpers/functions.php';
// }

?>